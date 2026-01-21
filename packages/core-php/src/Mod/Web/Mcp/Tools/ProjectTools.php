<?php

namespace Core\Mod\Web\Mcp\Tools;

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Project;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class ProjectTools extends BaseBioTool
{
    protected string $name = 'project_tools';

    protected string $description = 'Manage projects for organizing bio links';

    public function handle(Request $request): Response
    {
        $action = $request->get('action');
        $userId = $request->get('user_id');

        return match ($action) {
            'list' => $this->listProjects($userId),
            'create' => $this->createProject($userId, $request),
            'update' => $this->updateProject($request),
            'delete' => $this->deleteProject($request->get('project_id')),
            'move_biolink' => $this->moveBioLinkToProject($request),
            default => $this->error('Invalid action', ['available' => ['list', 'create', 'update', 'delete', 'move_biolink']]),
        };
    }

    protected function listProjects(?int $userId): Response
    {
        $workspace = $this->getWorkspaceForUser($userId);
        if (! $workspace) {
            return $this->error('User or workspace not found');
        }

        $projects = Project::where('workspace_id', $workspace->id)
            ->withCount('biolinks')
            ->get();

        return $this->json([
            'projects' => $projects->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'color' => $project->color,
                'biolinks_count' => $project->biolinks_count,
                'created_at' => $project->created_at->toIso8601String(),
            ]),
            'total' => $projects->count(),
        ]);
    }

    protected function createProject(?int $userId, Request $request): Response
    {
        $workspace = $this->getWorkspaceForUser($userId);
        if (! $workspace) {
            return $this->error('User or workspace not found');
        }

        $name = $request->get('name');
        if (! $name) {
            return $this->error('name is required');
        }

        $project = Project::create([
            'workspace_id' => $workspace->id,
            'user_id' => $userId,
            'name' => $name,
            'color' => $request->get('color', '#6366f1'),
        ]);

        return $this->json([
            'ok' => true,
            'project_id' => $project->id,
            'name' => $project->name,
        ]);
    }

    protected function updateProject(Request $request): Response
    {
        $projectId = $request->get('project_id');
        if (! $projectId) {
            return $this->error('project_id is required');
        }

        $project = Project::find($projectId);
        if (! $project) {
            return $this->error('Project not found');
        }

        $updateData = [];
        if ($request->has('name')) {
            $updateData['name'] = $request->get('name');
        }
        if ($request->has('color')) {
            $updateData['color'] = $request->get('color');
        }

        $project->update($updateData);

        return $this->json([
            'ok' => true,
            'project_id' => $project->id,
            'name' => $project->name,
        ]);
    }

    protected function deleteProject(?int $projectId): Response
    {
        if (! $projectId) {
            return $this->error('project_id is required');
        }

        $project = Project::find($projectId);
        if (! $project) {
            return $this->error('Project not found');
        }

        // Unassign biolinks from this project
        Page::where('project_id', $projectId)->update(['project_id' => null]);

        $name = $project->name;
        $project->delete();

        return $this->json([
            'ok' => true,
            'deleted_project' => $name,
        ]);
    }

    protected function moveBioLinkToProject(Request $request): Response
    {
        $biolinkId = $request->get('biolink_id');
        $projectId = $request->get('project_id'); // null to remove from project

        if (! $biolinkId) {
            return $this->error('biolink_id is required');
        }

        $biolink = Page::find($biolinkId);
        if (! $biolink) {
            return $this->error('Bio link not found');
        }

        if ($projectId) {
            $project = Project::find($projectId);
            if (! $project) {
                return $this->error('Project not found');
            }
        }

        $biolink->update(['project_id' => $projectId]);

        return $this->json([
            'ok' => true,
            'biolink_id' => $biolink->id,
            'project_id' => $projectId,
        ]);
    }
}
