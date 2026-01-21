<?php

namespace Core\Mod\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class QueryDatabase extends Tool
{
    protected string $description = 'Execute a read-only SQL SELECT query against the database';

    public function handle(Request $request): Response
    {
        $query = $request->input('query');

        if (! preg_match('/^\s*SELECT\s/i', $query)) {
            return Response::text(json_encode(['error' => 'Only SELECT queries are allowed']));
        }

        try {
            $results = DB::select($query);

            return Response::text(json_encode($results, JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            return Response::text(json_encode(['error' => $e->getMessage()]));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string('SQL SELECT query to execute'),
        ];
    }
}
