<?php

namespace Core\Mod\Web\Mcp\Tools;

use Core\Mod\Web\Models\Domain;
use Core\Mod\Web\Services\DomainVerificationService;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class DomainTools extends BaseBioTool
{
    protected string $name = 'domain_tools';

    protected string $description = 'Manage custom domains for bio pages: list, add, verify, delete';

    public function handle(Request $request): Response
    {
        $action = $request->get('action');
        $userId = $request->get('user_id');

        return match ($action) {
            'list' => $this->listDomains($userId),
            'add' => $this->addDomain($userId, $request),
            'verify' => $this->verifyDomain($request->get('domain_id')),
            'delete' => $this->deleteDomain($request->get('domain_id')),
            default => $this->error('Invalid action', ['available' => ['list', 'add', 'verify', 'delete']]),
        };
    }

    protected function listDomains(?int $userId): Response
    {
        $workspace = $this->getWorkspaceForUser($userId);
        if (! $workspace) {
            return $this->error('User or workspace not found');
        }

        $domains = Domain::where('workspace_id', $workspace->id)
            ->with('biolinks')
            ->get();

        return $this->json([
            'domains' => $domains->map(fn (Domain $domain) => [
                'id' => $domain->id,
                'host' => $domain->host,
                'scheme' => $domain->scheme,
                'is_enabled' => $domain->is_enabled,
                'verification_status' => $domain->verification_status,
                'verified_at' => $domain->verified_at?->toIso8601String(),
                'biolinks_count' => $domain->biolinks->count(),
                'created_at' => $domain->created_at->toIso8601String(),
            ]),
            'total' => $domains->count(),
        ]);
    }

    protected function addDomain(?int $userId, Request $request): Response
    {
        $workspace = $this->getWorkspaceForUser($userId);
        if (! $workspace) {
            return $this->error('User or workspace not found');
        }

        $host = $request->get('host');
        if (! $host) {
            return $this->error('host is required');
        }

        $verificationService = app(DomainVerificationService::class);

        // Validate and normalise
        $host = $verificationService->normaliseHost($host);

        if (! $verificationService->validateDomainFormat($host)) {
            return $this->error('Invalid domain format');
        }

        if ($verificationService->isDomainReserved($host)) {
            return $this->error('This domain is reserved');
        }

        if (Domain::where('host', $host)->exists()) {
            return $this->error('Domain already registered');
        }

        $domain = Domain::create([
            'workspace_id' => $workspace->id,
            'user_id' => $userId,
            'host' => $host,
            'scheme' => 'https',
            'is_enabled' => false,
            'verification_status' => Domain::VERIFICATION_PENDING,
        ]);

        $domain->generateVerificationToken();
        $instructions = $verificationService->getDnsInstructions($domain);

        return $this->json([
            'ok' => true,
            'domain_id' => $domain->id,
            'host' => $domain->host,
            'verification_status' => $domain->verification_status,
            'dns_instructions' => $instructions,
            'message' => 'Add one of the DNS records below to verify your domain ownership.',
        ]);
    }

    protected function verifyDomain(?int $domainId): Response
    {
        if (! $domainId) {
            return $this->error('domain_id is required');
        }

        $domain = Domain::find($domainId);
        if (! $domain) {
            return $this->error('Domain not found');
        }

        $verificationService = app(DomainVerificationService::class);
        $verified = $verificationService->verify($domain);

        $domain->refresh();

        return $this->json([
            'ok' => $verified,
            'domain_id' => $domain->id,
            'host' => $domain->host,
            'verification_status' => $domain->verification_status,
            'is_enabled' => $domain->is_enabled,
            'message' => $verified
                ? 'Domain verified successfully and is now enabled.'
                : 'Verification failed. Please check your DNS records and try again.',
        ]);
    }

    protected function deleteDomain(?int $domainId): Response
    {
        if (! $domainId) {
            return $this->error('domain_id is required');
        }

        $domain = Domain::find($domainId);
        if (! $domain) {
            return $this->error('Domain not found');
        }

        $host = $domain->host;
        $domain->delete();

        return $this->json([
            'ok' => true,
            'deleted_host' => $host,
        ]);
    }
}
