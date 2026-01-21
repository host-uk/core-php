<?php

namespace Core\Mod\Web\Mcp\Tools;

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Submission;
use Carbon\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class SubmissionTools extends BaseBioTool
{
    protected string $name = 'submission_tools';

    protected string $description = 'Manage form submissions from bio pages';

    public function handle(Request $request): Response
    {
        $action = $request->get('action');

        return match ($action) {
            'list' => $this->listSubmissions($request),
            'export' => $this->exportSubmissions($request),
            default => $this->error('Invalid action', ['available' => ['list', 'export']]),
        };
    }

    protected function listSubmissions(Request $request): Response
    {
        $biolinkId = $request->get('biolink_id');
        if (! $biolinkId) {
            return $this->error('biolink_id is required');
        }

        $biolink = Page::find($biolinkId);
        if (! $biolink) {
            return $this->error('Bio link not found');
        }

        $query = Submission::where('biolink_id', $biolinkId)
            ->with('block');

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        $limit = min((int) $request->get('limit', 50), 100);
        $offset = (int) $request->get('offset', 0);

        $total = $query->count();
        $submissions = $query->latest()
            ->skip($offset)
            ->take($limit)
            ->get();

        return $this->json([
            'submissions' => $submissions->map(fn (Submission $sub) => [
                'id' => $sub->id,
                'type' => $sub->type,
                'summary' => $sub->summary,
                'data' => $sub->data?->toArray(),
                'country_code' => $sub->country_code,
                'block_id' => $sub->block_id,
                'block_type' => $sub->block?->type,
                'created_at' => $sub->created_at->toIso8601String(),
            ]),
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    protected function exportSubmissions(Request $request): Response
    {
        $biolinkId = $request->get('biolink_id');
        if (! $biolinkId) {
            return $this->error('biolink_id is required');
        }

        $biolink = Page::find($biolinkId);
        if (! $biolink) {
            return $this->error('Bio link not found');
        }

        $query = Submission::where('biolink_id', $biolinkId);

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        if ($startDate = $request->get('start_date')) {
            $query->where('created_at', '>=', Carbon::parse($startDate));
        }
        if ($endDate = $request->get('end_date')) {
            $query->where('created_at', '<=', Carbon::parse($endDate)->endOfDay());
        }

        $submissions = $query->latest()->get();
        $format = $request->get('format', 'json');

        if ($format === 'csv') {
            $lines = [];
            $headers = ['type', 'name', 'email', 'phone', 'message', 'country', 'submitted_at'];
            $lines[] = implode(',', $headers);

            foreach ($submissions as $sub) {
                $export = $sub->toExportArray();
                $row = [];
                foreach ($headers as $header) {
                    $value = $export[$header] ?? '';
                    $row[] = '"'.str_replace('"', '""', (string) $value).'"';
                }
                $lines[] = implode(',', $row);
            }

            return Response::text(implode("\n", $lines));
        }

        return $this->json([
            'biolink_id' => $biolinkId,
            'total' => $submissions->count(),
            'submissions' => $submissions->map(fn ($sub) => $sub->toExportArray()),
        ]);
    }
}
