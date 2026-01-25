# Core-MCP TODO

## MCP Playground UI

**Priority:** Low
**Context:** Interactive UI for testing MCP tools.

### Requirements

- Tool browser with documentation
- Input form builder from tool schemas
- Response viewer with formatting
- Session/conversation persistence
- Example prompts per tool

---

## Workspace Context Security

**Priority:** High (Security)
**Context:** MCP falls back to `workspace_id = 1` when no context provided.

### Current Issue

```php
// Dangerous fallback
$workspaceId = $context->workspaceId ?? 1;
```

### Solution

```php
// Throw instead of fallback
if (!$context->workspaceId) {
    throw new MissingWorkspaceContextException(
        'MCP tool requires workspace context'
    );
}
```

### Requirements

- Remove all hardcoded workspace fallbacks
- Require explicit workspace context for all workspace-scoped tools
- Add context validation middleware
- Audit all tools for proper scoping

---

## Tool Usage Analytics

**Priority:** Low
**Context:** Track tool usage patterns for optimisation.

### Requirements

- Per-tool call counts
- Average response times
- Error rates by tool
- Popular tool combinations
- Dashboard in admin

---

## Query Security

**Priority:** Critical (Security)
**Context:** QueryDatabase tool regex check bypassed by UNION/stacked queries.

### Current Issue

Regex-based SQL validation is insufficient.

### Solution

1. **Read-only database user** - Primary defence
2. **Query whitelist** - Only allow specific query patterns
3. **Parameterised views** - Expose data through views, not raw queries

### Implementation

```php
// Use read-only connection
DB::connection('readonly')->select($query);

// Or whitelist approach
if (!$this->isWhitelistedQuery($query)) {
    throw new ForbiddenQueryException();
}
```
