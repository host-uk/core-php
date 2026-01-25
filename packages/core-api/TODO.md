# Core-API TODO

## Webhook Signing (Outbound)

**Priority:** Medium
**Context:** No request signing for outbound webhooks. Recipients cannot verify requests came from our platform.

### Implementation

```php
// When sending webhooks
$payload = json_encode($data);
$signature = hash_hmac('sha256', $payload, $webhookSecret);

$response = Http::withHeaders([
    'X-Signature' => $signature,
    'X-Timestamp' => now()->timestamp,
])->post($url, $data);
```

### Requirements

- Generate per-endpoint webhook secrets
- Sign all outbound webhook requests
- Include timestamp to prevent replay attacks
- Document verification for recipients

---

## OpenAPI/Swagger Documentation

**Priority:** Low
**Context:** No auto-generated API documentation.

### Options

1. **dedoc/scramble** - Auto-generates from routes/controllers
2. **darkaonline/l5-swagger** - Annotation-based
3. **Custom** - Generate from route definitions

### Requirements

- Auto-discover API routes from modules
- Support module-specific doc sections
- Serve at `/api/docs` endpoint
- Include authentication examples

---

## API Key Security

**Priority:** Medium (Security)
**Context:** API keys use SHA-256 without salt.

### Current

```php
$hashedKey = hash('sha256', $rawKey);
```

### Recommended

```php
// Use Argon2 or bcrypt
$hashedKey = Hash::make($rawKey);

// Verify
Hash::check($providedKey, $storedHash);
```

### Notes

- Migration needed for existing keys
- Consider key rotation mechanism
- Add key scopes/permissions

---

## Rate Limiting Improvements

**Priority:** Medium
**Context:** Basic rate limiting exists but needs granularity.

### Requirements

- Per-endpoint rate limits
- Per-workspace rate limits
- Burst allowance configuration
- Rate limit headers in responses
