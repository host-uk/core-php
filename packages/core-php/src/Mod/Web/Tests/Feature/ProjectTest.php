<?php

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Project;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create();
    $this->user->hostWorkspaces()->attach($this->workspace->id, ['is_default' => true]);
});

it('can create a project', function () {
    $project = Project::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'name' => 'Marketing Campaign',
        'color' => '#8b5cf6',
    ]);

    expect($project)->toBeInstanceOf(Project::class)
        ->and($project->name)->toBe('Marketing Campaign')
        ->and($project->color)->toBe('#8b5cf6');
});

it('provides available colours', function () {
    $colours = Project::COLOURS;

    expect($colours)->toBeArray()
        ->and($colours)->toHaveKey('#6366f1')
        ->and($colours)->toHaveKey('#8b5cf6');
});

it('can assign biolinks to a project', function () {
    $project = Project::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'name' => 'Test Project',
    ]);

    $biolink1 = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'project_id' => $project->id,
        'type' => 'biolink',
        'url' => 'link1',
    ]);

    $biolink2 = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'project_id' => $project->id,
        'type' => 'biolink',
        'url' => 'link2',
    ]);

    expect($project->biolinks)->toHaveCount(2)
        ->and($biolink1->project->id)->toBe($project->id);
});

it('supports soft deletes', function () {
    $project = Project::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'name' => 'To Delete',
    ]);

    $project->delete();

    expect(Project::find($project->id))->toBeNull()
        ->and(Project::withTrashed()->find($project->id))->not->toBeNull();
});

it('nullifies biolink project_id when project is deleted', function () {
    $project = Project::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'name' => 'Delete Test',
    ]);

    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'project_id' => $project->id,
        'type' => 'biolink',
        'url' => 'orphan-link',
    ]);

    // Force delete to trigger cascade
    $project->forceDelete();

    expect($biolink->fresh()->project_id)->toBeNull();
});

it('belongs to a workspace', function () {
    $project = Project::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'name' => 'Workspace Test',
    ]);

    expect($project->workspace->id)->toBe($this->workspace->id);
});

it('can filter biolinks by project', function () {
    $project1 = Project::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'name' => 'Project 1',
    ]);

    $project2 = Project::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'name' => 'Project 2',
    ]);

    Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'project_id' => $project1->id,
        'type' => 'biolink',
        'url' => 'proj1-link',
    ]);

    Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'project_id' => $project2->id,
        'type' => 'biolink',
        'url' => 'proj2-link',
    ]);

    Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'project_id' => null,
        'type' => 'biolink',
        'url' => 'no-proj-link',
    ]);

    $project1Biolinks = Page::where('project_id', $project1->id)->get();
    $unassignedBiolinks = Page::whereNull('project_id')->get();

    expect($project1Biolinks)->toHaveCount(1)
        ->and($project1Biolinks->first()->url)->toBe('proj1-link')
        ->and($unassignedBiolinks)->toHaveCount(1);
});
