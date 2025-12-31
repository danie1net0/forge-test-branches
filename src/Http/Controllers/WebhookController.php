<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Http\Controllers;

use Ddr\ForgeTestBranches\Models\ReviewEnvironment;
use Ddr\ForgeTestBranches\Services\EnvironmentBuilder;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Routing\Controller;
use Throwable;

class WebhookController extends Controller
{
    public function handle(Request $request, EnvironmentBuilder $builder): JsonResponse
    {
        if (! $this->isPushEvent($request)) {
            return response()->json(['message' => 'Event ignored']);
        }

        $payload = $request->all();

        if (! $this->isBranchDeleted($request, $payload)) {
            return response()->json(['message' => 'Not a branch deletion']);
        }

        $branch = $this->extractBranch($payload);
        $environment = ReviewEnvironment::query()->where('branch', $branch)->first();

        if (! $environment) {
            return response()->json(['message' => 'Environment not found']);
        }

        try {
            $builder->destroy($environment);

            return response()->json(['message' => 'Environment destroyed successfully']);
        } catch (Throwable $throwable) {
            return response()->json(['message' => 'Error destroying environment', 'error' => $throwable->getMessage()], 500);
        }
    }

    protected function isPushEvent(Request $request): bool
    {
        if ($this->isGitHubRequest($request)) {
            return $request->header('X-GitHub-Event') === 'delete';
        }

        return $request->header('X-Gitlab-Event') === 'Push Hook';
    }

    protected function isGitHubRequest(Request $request): bool
    {
        return $request->hasHeader('X-GitHub-Event');
    }

    /** @param array<string, mixed> $payload */
    protected function isBranchDeleted(Request $request, array $payload): bool
    {
        if ($this->isGitHubRequest($request)) {
            return ($payload['ref_type'] ?? '') === 'branch';
        }

        return ($payload['after'] ?? '') === '0000000000000000000000000000000000000000';
    }

    /** @param array<string, mixed> $payload */
    protected function extractBranch(array $payload): string
    {
        $ref = $payload['ref'] ?? '';

        return str_replace('refs/heads/', '', $ref);
    }
}
