# Core-MCP TODO

## Security

- [ ] **Critical: Fix Database Connection Fallback** - `QueryDatabase` tool falls back to the default database connection if `mcp.database.connection` is not defined or invalid. This risks exposing write access. Must throw an exception or strictly require a valid read-only connection.

- [ ] **High: Strengthen SQL Validator Regex** - The current whitelist regex `/.+/` in the WHERE clause is too permissive, allowing boolean-based blind injection. Consider a stricter parser or document the read-only limitation clearly.

## Features

- [ ] **Explain Plan** - Add option to `QueryDatabase` tool to run `EXPLAIN` first, allowing the agent to verify cost/safety before execution.

---

*See `changelog/2026/jan/` for completed features.*
