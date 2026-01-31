---
title: Architecture
description: Technical architecture of the core-support helpdesk module
updated: 2026-01-29
---

# Architecture

This document describes the technical architecture of the `core-support` package, a helpdesk and customer support module for the Core PHP Framework.

## Overview

`core-support` provides a unified inbox for customer communications across multiple channels (email, live chat, WhatsApp, API). It includes features like:

- Multi-mailbox support with IMAP/SMTP integration
- Live chat widgets for website embedding
- SLA policy enforcement with breach tracking
- Email forwarding rules with conditional routing
- Role-based agent permissions
- Data retention and storage quota management

## Module Structure

```
core-support/
├── Boot.php                 # Service provider + event handlers
├── Models/                  # Eloquent models
│   ├── Conversation.php     # Support ticket/thread container
│   ├── Thread.php           # Individual messages
│   ├── Mailbox.php          # Email inbox configuration
│   ├── SupportCustomer.php  # Contact records
│   ├── ChatWidget.php       # Live chat configuration
│   ├── SlaPolicy.php        # SLA rules
│   ├── ForwardingRule.php   # Email routing rules
│   └── ...
├── Controllers/Api/         # REST API controllers
├── Services/                # Business logic layer
│   ├── ConversationService.php
│   ├── EmailParserService.php
│   ├── SlaService.php
│   ├── EntitlementService.php
│   ├── ForwardingRuleService.php
│   ├── RetentionService.php
│   └── ...
├── View/Modal/              # Livewire components
│   ├── Web/                 # User-facing components
│   └── Admin/               # Admin panel components
├── Jobs/                    # Queue jobs
├── Events/                  # Domain events
├── Middleware/              # HTTP middleware
├── Console/                 # Artisan commands
├── database/migrations/     # Database schema
└── tests/                   # Pest test suites
```

## Event-Driven Loading

The module uses the Core PHP Framework's event-driven loading pattern. The `Boot` class declares which lifecycle events it responds to:

```php
public static array $listens = [
    AdminPanelBooting::class => 'onAdminPanel',
    ApiRoutesRegistering::class => 'onApiRoutes',
    WebRoutesRegistering::class => 'onWebRoutes',
];
```

This ensures the module only loads its routes, views, and components when the relevant subsystem is activated.

## Core Domain Models

### Conversation

The central entity representing a support ticket. Key attributes:

- **workspace_id**: Multi-tenant isolation
- **mailbox_id**: Which inbox this belongs to
- **customer_id**: The contact who initiated
- **status**: active, pending, closed, spam
- **channel**: email, chat, whatsapp, api
- **sla_***: SLA tracking fields

Uses `BelongsToWorkspace` trait for automatic tenant scoping.

### Thread

Individual messages within a conversation:

- **type**: customer, message (agent), note (internal), lineitem (system)
- **source**: web, email, api, chat
- **body**: Message content (HTML supported)
- **headers**: Email headers for threading

### Mailbox

Email inbox configuration with IMAP/SMTP credentials:

- Credentials are encrypted using Laravel's `encrypt()` function
- Supports auto-reply configuration
- Can have multiple chat widgets attached
- Has forwarding rules for conditional routing

### SupportCustomer

Contact records separate from system users:

- Linked to workspace, not global
- Can be associated with a User for authenticated customers
- Tracks last activity for analytics

## Service Layer

### ConversationService

Central orchestrator for conversation operations:

- `create()` - Creates conversation with initial thread, assigns SLA
- `reply()` - Adds agent reply or internal note
- `addCustomerMessage()` - Adds customer message, reopens if closed
- `findOrCreateFromEmail()` - Handles inbound email threading
- `merge()` - Combines two conversations
- `assign()` / `changeStatus()` - State management

### EmailParserService

Processes inbound emails from IMAP:

- Extracts headers, body, attachments
- Cleans quoted text and signatures
- Sanitises attachment filenames (security)
- Triggers forwarding rules
- Queues auto-reply if enabled

### SlaService

Manages SLA policy enforcement:

- Assigns policies based on priority
- Calculates due dates for first response and resolution
- Updates status: on_track, at_risk, breached
- Provides metrics and reporting

### EntitlementService

Integrates with Core tenant entitlements:

- Checks feature availability (chat_widget, api_access)
- Enforces resource limits (mailboxes, agents, storage)
- Provides usage summaries for billing/upgrade prompts

### ForwardingRuleService

Evaluates conditional routing rules:

- Condition types: sender, subject, body, header, recipient
- Operators: contains, equals, matches (regex), starts/ends with
- Actions: forward, copy to mailbox, add tag, set priority, mark spam
- Supports AND/OR logic for conditions

## Data Flow

### Inbound Email

```
IMAP Server
    ↓
FetchEmails Job (scheduled)
    ↓
EmailParserService.process()
    ↓
ForwardingRuleService.processRules() → Apply actions
    ↓
ConversationService.findOrCreateFromEmail()
    ↓
SendAutoReply Job (if enabled)
```

### Live Chat

```
Widget JavaScript
    ↓
ChatWidgetController.init() → Returns config + contact session
    ↓
ChatWidgetController.start() → Creates conversation
    ↓
ChatWidgetController.message() → Adds to thread
    ↓
CustomerTyping Event (broadcast)
```

### Agent Reply

```
Inbox Component
    ↓
ConversationView Component
    ↓
ConversationService.reply()
    ↓
SlaService.recordFirstResponse() (if applicable)
    ↓
SendReply Job → SMTP
```

## Multi-Tenancy

All data is scoped by `workspace_id`. The `BelongsToWorkspace` trait provides:

- Automatic workspace assignment on create
- Global scope filtering queries
- Exception if no workspace context

Conversations are also scoped through their mailbox relationship for double isolation.

## Authentication & Authorisation

### Authenticated Routes

- Web routes use `auth` middleware
- API routes use `auth` + `support.entitlement:api_access`
- Entitlement middleware checks workspace plan features

### Public Chat Widget

- Uses token-based authentication (`website_token`)
- Rate limited to prevent abuse
- CORS enabled for cross-origin embedding
- Optional HMAC verification for visitor identity

### Agent Permissions

The `SupportRole` system provides granular permissions:

- `view_conversations`, `reply_to_conversations`, `assign_conversations`
- `manage_mailboxes`, `manage_sla`, `manage_roles`
- `access_reports`, `delete_conversations`

## Background Jobs

| Job | Purpose | Schedule |
|-----|---------|----------|
| `FetchEmails` | Poll IMAP for new messages | Cron (configurable) |
| `CheckSlaStatus` | Update SLA breach status | Every minute |
| `SendReply` | Send agent reply via SMTP | Queue |
| `SendAutoReply` | Send auto-reply to new conversations | Queue |

## Events

| Event | When Fired | Typical Listeners |
|-------|------------|-------------------|
| `NewConversation` | Conversation created | Notifications, webhooks |
| `ThreadAdded` | Message added | Real-time updates |
| `ConversationUpdated` | Status/assignment changed | Activity log |
| `CustomerTyping` | Chat typing indicator | Broadcast to agents |
| `AgentTyping` | Agent typing indicator | Broadcast to customer |

## Database Schema

### Core Tables

- `support_mailboxes` - Inbox configurations
- `support_conversations` - Tickets
- `support_messages` / `support_threads` - Messages (schema needs reconciliation)
- `support_canned_responses` - Saved replies
- `support_sla_policies` - SLA rules
- `support_forwarding_rules` - Email routing
- `support_roles` / `support_agent_roles` - Permissions

### Indexes

Key indexes for performance:

- `(workspace_id, status)` on conversations
- `(status, priority)` for inbox sorting
- `(sla_status, sla_first_response_due_at)` for SLA queries
- `message_id` on threads for email threading

## Configuration

The module uses Laravel config with defaults:

```php
// config/support.php (planned)
return [
    'retention' => [
        'enabled' => true,
        'default_days' => 365,
        'cleanup_batch_size' => 100,
    ],
    'email' => [
        'fetch_limit' => 50,
        'fetch_lookback_days' => 7,
    ],
    'chat' => [
        'session_timeout_minutes' => 30,
    ],
];
```

Currently some settings are hardcoded and should be extracted.

## Extension Points

### Custom Channels

Add new channels by:

1. Adding constant to `Conversation::CHANNEL_*`
2. Creating controller for channel-specific API
3. Implementing service for message processing

### Custom Actions

Extend `ForwardingRuleService::executeAction()` for custom routing actions.

### Webhooks

Listen to domain events (`NewConversation`, `ThreadAdded`) to trigger external webhooks.
