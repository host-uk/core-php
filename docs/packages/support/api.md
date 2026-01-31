---
title: API Reference
description: REST API documentation for core-support
updated: 2026-01-29
---

# API Reference

This document describes the REST API endpoints provided by the `core-support` module.

## Authentication

### Authenticated Endpoints

Most endpoints require authentication via Laravel session or API token. Include the workspace context in requests.

**Required middleware**: `auth`, `support.entitlement:api_access`

### Public Chat Widget Endpoints

Chat widget endpoints use token-based authentication via the `token` parameter. No session required.

**Required middleware**: `support.widget.cors`, `throttle`

## Base URL

All endpoints are prefixed with `/api/support/`.

---

## Conversations

### List Conversations

```
GET /api/support/conversations
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| mailbox_id | integer | Filter by mailbox |
| status | string | Filter by status (active, pending, closed, spam) |
| channel | string | Filter by channel (email, chat, whatsapp, api) |
| assigned_to | integer\|string | Filter by assignee ID, or "unassigned" |
| search | string | Search subject, number, or customer email |
| per_page | integer | Results per page (default: 25) |

**Response:**

```json
{
  "data": [
    {
      "id": 1,
      "number": 42,
      "subject": "Help with my account",
      "status": "active",
      "channel": "email",
      "customer": {
        "id": 5,
        "email": "customer@example.com",
        "display_name": "John Doe"
      },
      "mailbox": {
        "id": 1,
        "name": "Support"
      },
      "assignedUser": null,
      "last_reply_at": "2026-01-29T10:30:00Z",
      "created_at": "2026-01-28T15:00:00Z"
    }
  ],
  "links": { ... },
  "meta": { ... }
}
```

### Get Conversation

```
GET /api/support/conversations/{id}
```

**Response:**

```json
{
  "data": {
    "id": 1,
    "number": 42,
    "subject": "Help with my account",
    "status": "active",
    "channel": "email",
    "sla_status": "on_track",
    "sla_first_response_due_at": "2026-01-29T12:00:00Z",
    "customer": { ... },
    "mailbox": { ... },
    "assignedUser": { ... },
    "threads": [
      {
        "id": 1,
        "type": "customer",
        "body": "I need help...",
        "created_at": "2026-01-28T15:00:00Z",
        "user": null,
        "attachments": []
      },
      {
        "id": 2,
        "type": "message",
        "body": "Thanks for reaching out...",
        "created_at": "2026-01-28T15:30:00Z",
        "user": { "id": 1, "name": "Agent" },
        "attachments": []
      }
    ]
  }
}
```

### Create Conversation

```
POST /api/support/conversations
```

**Request Body:**

```json
{
  "mailbox_id": 1,
  "customer_email": "customer@example.com",
  "customer_name": "John Doe",
  "subject": "Help with my account",
  "body": "I need assistance with...",
  "channel": "api"
}
```

**Response:** `201 Created`

```json
{
  "data": { ... },
  "message": "Conversation created successfully"
}
```

### Reply to Conversation

```
POST /api/support/conversations/{id}/reply
```

**Request Body:**

```json
{
  "body": "Thanks for your message. Here's how to resolve this...",
  "type": "reply"
}
```

| Field | Type | Description |
|-------|------|-------------|
| body | string | Reply content (required, max 50000 chars) |
| type | string | "reply" or "note" (default: reply) |

**Response:** `201 Created`

```json
{
  "data": {
    "id": 3,
    "type": "message",
    "body": "Thanks for your message...",
    "created_at": "2026-01-29T11:00:00Z",
    "user": { ... }
  },
  "message": "Reply added successfully"
}
```

### Update Conversation Status

```
PATCH /api/support/conversations/{id}/status
```

**Request Body:**

```json
{
  "status": "closed"
}
```

Valid statuses: `active`, `pending`, `closed`, `spam`

### Assign Conversation

```
PATCH /api/support/conversations/{id}/assign
```

**Request Body:**

```json
{
  "user_id": 5
}
```

Set `user_id` to `null` to unassign.

### Delete Conversation

```
DELETE /api/support/conversations/{id}
```

Soft deletes the conversation.

---

## Mailboxes

### List Mailboxes

```
GET /api/support/mailboxes
```

### Create Mailbox

```
POST /api/support/mailboxes
```

**Middleware**: `support.limit:mailboxes` (checks plan limits)

### Get Mailbox

```
GET /api/support/mailboxes/{id}
```

### Update Mailbox

```
PUT /api/support/mailboxes/{id}
```

### Delete Mailbox

```
DELETE /api/support/mailboxes/{id}
```

### Test IMAP Connection

```
POST /api/support/mailboxes/{id}/test-imap
```

**Rate limit**: 5/minute

---

## Customers

### List Customers

```
GET /api/support/customers
```

### Create Customer

```
POST /api/support/customers
```

### Get Customer

```
GET /api/support/customers/{id}
```

### Update Customer

```
PUT /api/support/customers/{id}
```

### Delete Customer

```
DELETE /api/support/customers/{id}
```

### Get Customer Conversations

```
GET /api/support/customers/{id}/conversations
```

---

## Chat Widget (Public)

These endpoints are public and authenticated via widget token.

### Initialise Widget

```
POST /api/support/chat/init
```

**Request Body:**

```json
{
  "token": "abc123def456...",
  "visitor_id": "optional-visitor-identifier",
  "page_url": "https://example.com/page",
  "page_title": "Product Page"
}
```

**Response:**

```json
{
  "success": true,
  "widget": {
    "id": 1,
    "name": "Support",
    "color": "#0066cc",
    "welcome_title": "Hi there!",
    "welcome_tagline": "How can we help?",
    "away_message": "We'll get back to you soon.",
    "reply_time": "usually replies in a few minutes",
    "require_email": true
  },
  "contact": {
    "id": 123,
    "visitor_id": "abc-123",
    "pubsub_channel": "support.chat.123"
  },
  "active_conversation": null
}
```

### Start Chat

```
POST /api/support/chat/start
```

**Request Body:**

```json
{
  "token": "abc123def456...",
  "contact_id": 123,
  "email": "visitor@example.com",
  "name": "Visitor Name",
  "message": "Hi, I have a question about..."
}
```

**Response:** `201 Created`

```json
{
  "success": true,
  "conversation": {
    "id": 456,
    "number": 100,
    "subject": "Chat from Visitor Name"
  },
  "thread": {
    "id": 789,
    "body": "Hi, I have a question about...",
    "created_at": "2026-01-29T11:00:00.000Z"
  }
}
```

### Send Message

```
POST /api/support/chat/message
```

**Request Body:**

```json
{
  "token": "abc123def456...",
  "contact_id": 123,
  "conversation_id": 456,
  "message": "Thanks for the help!"
}
```

### Get Chat History

```
GET /api/support/chat/history
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| token | string | Widget token (required) |
| contact_id | integer | Contact ID (required) |
| conversation_id | integer | Conversation ID (required) |

**Response:**

```json
{
  "success": true,
  "messages": [
    {
      "id": 1,
      "type": "customer",
      "body": "Hi, I have a question...",
      "agent_name": null,
      "created_at": "2026-01-29T11:00:00.000Z"
    },
    {
      "id": 2,
      "type": "agent",
      "body": "Hello! Happy to help.",
      "agent_name": "Support Agent",
      "created_at": "2026-01-29T11:05:00.000Z"
    }
  ]
}
```

### Typing Indicator

```
POST /api/support/chat/typing
```

**Request Body:**

```json
{
  "token": "abc123def456...",
  "contact_id": 123,
  "conversation_id": 456,
  "is_typing": true
}
```

---

## Error Responses

### 400 Bad Request

```json
{
  "error": "invalid_request",
  "message": "The request was invalid"
}
```

### 403 Forbidden

```json
{
  "error": "feature_not_available",
  "message": "Your plan does not include access to this feature.",
  "feature": "api_access",
  "current_plan": "free"
}
```

### 403 Limit Exceeded

```json
{
  "error": "limit_exceeded",
  "message": "You have reached your plan's limit for this resource.",
  "resource": "mailboxes",
  "limit": 1,
  "current_plan": "starter"
}
```

### 404 Not Found

```json
{
  "error": "widget_not_found",
  "message": "Invalid or inactive chat widget"
}
```

### 422 Validation Error

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "subject": ["The subject must be at least 3 characters."]
  }
}
```

---

## Rate Limits

| Endpoint | Limit |
|----------|-------|
| General authenticated | 60/minute |
| Mailbox creation | 10/minute |
| Mailbox update | 30/minute |
| IMAP test | 5/minute |
| Conversation creation | 20/minute |
| Conversation reply | 30/minute |
| Chat init | 60/minute |
| Chat start | 10/minute |
| Chat message | 30/minute |
| Chat typing | 120/minute |

Rate limit headers are included in responses:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 58
X-RateLimit-Reset: 1706527200
```
