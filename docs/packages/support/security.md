---
title: Security
description: Security considerations and audit notes for core-support
updated: 2026-01-29
---

# Security Considerations

This document outlines security considerations, implemented protections, and areas requiring attention in the `core-support` module.

## Implemented Protections

### Credential Encryption

IMAP and SMTP passwords are encrypted at rest using Laravel's `encrypt()` function:

```php
// Mailbox.php
public function setImapPasswordAttribute($value): void
{
    $this->attributes['imap_password'] = $value ? encrypt($value) : null;
}
```

The `webhook_secret` is similarly encrypted. These fields are also marked as `$hidden` to prevent accidental exposure in JSON serialisation.

### Multi-Tenant Isolation

All data access is scoped by workspace:

1. **Model Trait**: `BelongsToWorkspace` provides global scopes
2. **Controller Checks**: API controllers verify workspace ownership before operations
3. **Double Scoping**: Conversations are scoped via mailbox relationship

Example IDOR protection in `ConversationController`:

```php
protected function authorizeConversation(Request $request, Conversation $conversation): bool
{
    $conversation->loadMissing('mailbox');
    return $conversation->mailbox?->workspace_id === $request->workspace_id;
}
```

### Input Validation

API endpoints validate input using Laravel's validation:

```php
$validated = $request->validate([
    'mailbox_id' => 'required|exists:support_mailboxes,id',
    'customer_email' => 'required|email|max:255',
    'subject' => 'required|string|min:3|max:500',
    'body' => 'required|string|min:10|max:50000',
]);
```

### Attachment Security

Email attachments are sanitised before storage:

```php
// EmailParserService.php
$fileName = basename($attachment->getName());                    // Remove path
$fileName = preg_replace('/[\x00-\x1F\x7F]/', '', $fileName);   // Remove control chars
if (empty($fileName) || $fileName === '.' || $fileName === '..') {
    $fileName = 'attachment_' . uniqid();                        // Fallback
}
```

This prevents path traversal attacks via malicious filenames.

### XSS Protection in Thread Display

Thread body content is sanitised for display:

```php
// Thread.php - getSafeBodyAttribute()
$allowedTags = '<p><br><a><strong>...';
$safe = strip_tags($this->body ?? '', $allowedTags);
$safe = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $safe);  // Remove event handlers
$safe = preg_replace('/href\s*=\s*["\' ]?\s*javascript:[^"\'>\s]*/i', 'href="#"', $safe);  // Block javascript: URLs
```

### Rate Limiting

API endpoints are rate limited:

| Endpoint Group | Limit |
|----------------|-------|
| Authenticated API | 60/minute |
| Mailbox creation | 10/minute |
| Conversation creation | 20/minute |
| Chat widget init | 60/minute |
| Chat start | 10/minute |
| Chat message | 30/minute |
| Typing indicator | 120/minute |

### Secure Contact Form

The `SecureContactService` implements:

- HMAC-signed form tokens with expiry
- Honeypot field for bot detection
- Rate limiting per email address
- Optional E2E encryption support

```php
public function generateFormToken(Mailbox $mailbox, array $options = []): string
{
    $payload = [
        'mailbox_id' => $mailbox->id,
        'expires_at' => now()->addHours($options['ttl_hours'] ?? 24)->timestamp,
        'nonce' => Str::random(16),
        // ...
    ];
    return $this->signPayload($payload);
}
```

### Chat Widget Token Authentication

Chat widgets use randomly generated tokens instead of predictable IDs:

```php
// ChatWidget.php
static::creating(function (ChatWidget $widget) {
    $widget->website_token = Str::random(32);
    $widget->hmac_token = Str::random(64);
});
```

Optional HMAC verification for visitor identity prevents impersonation.

## Areas Requiring Attention

### P1: Critical Issues

#### 1. CSRF on Public Chat Endpoints

The `/api/support/chat/*` routes use `support.widget.cors` middleware which allows cross-origin requests. Standard CSRF protection is bypassed. While the widget token provides some protection, consider:

- Implementing token-based CSRF using the widget's HMAC capability
- Adding origin validation against `allowed_domains`
- Rate limiting by IP in addition to token

#### 2. Regex DoS in Forwarding Rules

User-supplied regex patterns in `ForwardingRuleService::matchRegex()` could cause ReDoS:

```php
// Current implementation - vulnerable
$pattern = '/' . $pattern . '/i';
return (bool) preg_match($pattern, $value);
```

Mitigations needed:
- Add pattern complexity limits
- Use `preg_match()` with timeout (PCRE JIT timeout)
- Validate pattern syntax before storage
- Consider using simpler wildcard matching instead

#### 3. Token Enumeration

`ChatWidget::findByToken()` allows unlimited lookups without rate limiting. An attacker could enumerate valid tokens. Add:

- Rate limiting on failed lookups
- Logging of failed attempts
- Consider using longer tokens (current: 32 chars)

#### 4. Bulk Operation Authorisation

`Inbox.php` bulk operations check mailbox ownership but not per-conversation permissions:

```php
public function bulkAssign(): void
{
    Conversation::where('mailbox_id', $this->selectedMailbox?->id)
        ->whereIn('id', $this->selectedConversations)
        ->update(['assigned_to' => $this->bulkAssignUserId]);
}
```

Should verify user has `PERM_ASSIGN_CONVERSATIONS` for each conversation.

### P2: Important Issues

#### 5. IMAP Credential Validation

`FetchEmails` job attempts connection without prior validation. Malicious configurations could:

- Cause excessive connection attempts to arbitrary hosts
- Be used for SSRF if IMAP library follows redirects
- Leak credentials to attacker-controlled servers

Add validation on mailbox save and restrict to known-safe IMAP patterns.

#### 6. Email Header Injection

While not currently exploitable, the email sending code should ensure headers cannot be injected:

```php
// ForwardingRuleService::forwardEmail()
$message->to($forwardTo)
    ->from($mailbox->email, $mailbox->name)
    ->subject("Fwd: {$subject}");
```

The `$forwardTo` is validated as email, but `$subject` comes from user input. Laravel's mailer should handle this, but explicit sanitisation would add defence in depth.

#### 7. Information Disclosure in Error Responses

Some error responses may leak internal information:

```php
return response()->json([
    'error' => 'configuration_error',
    'message' => 'Chat widget is not properly configured',
], 500);
```

Review all error responses to ensure they don't expose internal state.

### P3: Medium Issues

#### 8. Session Workspace Fallback

`EntitlementService` and `SearchService` fall back to session-stored workspace ID:

```php
$sessionWorkspaceId = session('workspace_id');
```

Session fixation or manipulation could lead to cross-tenant access. Prefer authenticated user's workspace only.

#### 9. Hardcoded Fallback Workspace

`SearchService` defaults to workspace ID 1:

```php
$this->workspaceId = $workspaceId ?? session('workspace_id', 1);
```

This could expose data if session is missing. Should fail securely instead.

#### 10. Activity Logging Gaps

Only some models use `LogsActivity` trait. Consider adding for:

- Thread (currently missing)
- SupportCustomer
- ForwardingRule
- ChatWidget

## Security Checklist for New Features

When adding features to core-support:

1. [ ] All database queries scoped by workspace_id
2. [ ] User input validated before use
3. [ ] API endpoints have appropriate rate limiting
4. [ ] Sensitive data encrypted at rest
5. [ ] Error messages don't leak internal details
6. [ ] File uploads sanitised and size-limited
7. [ ] External service connections validated
8. [ ] Activity logged for audit trail
9. [ ] Permission checks for all operations
10. [ ] CSRF protection or alternative for stateless endpoints

## Dependency Security

Key dependencies to monitor for vulnerabilities:

- `webklex/php-imap` - IMAP parsing
- `spatie/laravel-activitylog` - Activity logging
- Laravel framework (core)

Run `composer audit` regularly.

## Incident Response

If a security issue is discovered:

1. Assess scope and severity
2. Check activity logs for exploitation
3. Rotate affected credentials (IMAP passwords, webhook secrets, widget tokens)
4. Patch and deploy fix
5. Notify affected workspaces if data compromised
6. Document in post-incident review
