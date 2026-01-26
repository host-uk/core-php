<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MCP Database Security
    |--------------------------------------------------------------------------
    |
    | Configuration for the MCP QueryDatabase tool security measures.
    |
    */

    'database' => [
        /*
        |--------------------------------------------------------------------------
        | Read-Only Connection
        |--------------------------------------------------------------------------
        |
        | The database connection to use for MCP query execution. This should
        | be configured with a read-only database user for defence in depth.
        |
        | Set to null to use the default connection (not recommended for production).
        |
        */
        'connection' => env('MCP_DB_CONNECTION', 'mcp_readonly'),

        /*
        |--------------------------------------------------------------------------
        | Query Whitelist
        |--------------------------------------------------------------------------
        |
        | Enable or disable whitelist-based query validation. When enabled,
        | queries must match at least one pattern in the whitelist to execute.
        |
        */
        'use_whitelist' => env('MCP_DB_USE_WHITELIST', true),

        /*
        |--------------------------------------------------------------------------
        | Custom Whitelist Patterns
        |--------------------------------------------------------------------------
        |
        | Additional regex patterns to allow. The default whitelist allows basic
        | SELECT queries. Add patterns here for application-specific queries.
        |
        | Example:
        |     '/^\s*SELECT\s+.*\s+FROM\s+`?users`?\s+WHERE\s+id\s*=\s*\d+;?\s*$/i'
        |
        */
        'whitelist_patterns' => [
            // Add custom patterns here
        ],

        /*
        |--------------------------------------------------------------------------
        | Blocked Tables
        |--------------------------------------------------------------------------
        |
        | Tables that cannot be queried even with valid SELECT queries.
        | Use this to protect sensitive tables from MCP access.
        |
        */
        'blocked_tables' => [
            'users',
            'password_reset_tokens',
            'sessions',
            'personal_access_tokens',
            'failed_jobs',
        ],

        /*
        |--------------------------------------------------------------------------
        | Row Limit
        |--------------------------------------------------------------------------
        |
        | Maximum number of rows that can be returned from a query.
        | This prevents accidentally returning huge result sets.
        |
        */
        'max_rows' => env('MCP_DB_MAX_ROWS', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tool Usage Analytics
    |--------------------------------------------------------------------------
    |
    | Configuration for MCP tool usage analytics and metrics tracking.
    |
    */

    'analytics' => [
        /*
        |--------------------------------------------------------------------------
        | Enable Analytics
        |--------------------------------------------------------------------------
        |
        | Enable or disable tool usage analytics. When disabled, no metrics
        | will be recorded for tool executions.
        |
        */
        'enabled' => env('MCP_ANALYTICS_ENABLED', true),

        /*
        |--------------------------------------------------------------------------
        | Data Retention
        |--------------------------------------------------------------------------
        |
        | Number of days to retain analytics data before pruning.
        | Use the mcp:prune-metrics command to clean up old data.
        |
        */
        'retention_days' => env('MCP_ANALYTICS_RETENTION_DAYS', 90),

        /*
        |--------------------------------------------------------------------------
        | Batch Size
        |--------------------------------------------------------------------------
        |
        | Number of metrics to accumulate before flushing to the database.
        | Higher values improve write performance but may lose data on crashes.
        |
        */
        'batch_size' => env('MCP_ANALYTICS_BATCH_SIZE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Retention
    |--------------------------------------------------------------------------
    |
    | Configuration for MCP log retention and cleanup.
    |
    */

    'log_retention' => [
        /*
        |--------------------------------------------------------------------------
        | Detailed Logs Retention
        |--------------------------------------------------------------------------
        |
        | Number of days to retain detailed tool call logs.
        |
        */
        'days' => env('MCP_LOG_RETENTION_DAYS', 90),

        /*
        |--------------------------------------------------------------------------
        | Statistics Retention
        |--------------------------------------------------------------------------
        |
        | Number of days to retain aggregated statistics.
        | Should typically be longer than detailed logs.
        |
        */
        'stats_days' => env('MCP_LOG_RETENTION_STATS_DAYS', 365),
    ],

];
