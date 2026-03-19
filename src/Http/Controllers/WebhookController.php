<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Http\Controllers;

use Ddr\ForgeTestBranches\Data\EnvironmentData;
use Ddr\ForgeTestBranches\Logger;
use Ddr\ForgeTestBranches\Services\EnvironmentBuilder;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Routing\Controller;
use Throwable;

class WebhookController extends Controller
{
    public function handle(Request $request, EnvironmentBuilder $builder, Logger $logger): JsonResponse
    {
        $eventHeader = $request->header('X-Gitlab-Event') ?? $request->header('X-GitHub-Event') ?? 'unknown';
        $logger->info('Webhook received', [
            'event' => $eventHeader,
            'ref' => $request->input('ref'),
            'after' => $request->input('after'),
        ]);

        if (! $this->isPushEvent($request)) {
            $logger->debug('Webhook ignored: not a push/delete event', ['event' => $eventHeader]);

            return response()->json(['message' => 'Event ignored']);
        }

        $payload = $request->all();

        if (! $this->isBranchDeleted($request, $payload)) {
            $logger->debug('Webhook ignored: not a branch deletion', ['ref' => $payload['ref'] ?? '', 'after' => $payload['after'] ?? '']);

            return response()->json(['message' => 'Not a branch deletion']);
        }

        $branch = $this->extractBranch($payload);
        $provider = $this->isGitHubRequest($request) ? 'github' : 'gitlab';
        $logger->info('Branch deletion detected', ['branch' => $branch, 'provider' => $provider]);

        $environment = $builder->find($branch);

        if (! $environment instanceof EnvironmentData) {
            $logger->warning('Webhook: environment not found', ['branch' => $branch]);

            return response()->json(['message' => 'Environment not found']);
        }

        try {
            $builder->destroy($environment);
            $logger->info('Webhook: environment destroyed', ['branch' => $branch, 'domain' => $environment->domain]);

            return response()->json(['message' => 'Environment destroyed successfully']);
        } catch (Throwable $throwable) {
            $logger->error('Webhook: error destroying environment', ['branch' => $branch, 'error' => $throwable->getMessage()]);

            return response()->json(['message' => 'Error destroying environment'], 500);
        }
    }

    private function isPushEvent(Request $request): bool
    {
        if ($this->isGitHubRequest($request)) {
            return $request->header('X-GitHub-Event') === 'delete';
        }

        return $request->header('X-Gitlab-Event') === 'Push Hook';
    }

    private function isGitHubRequest(Request $request): bool
    {
        return $request->hasHeader('X-GitHub-Event');
    }

    /** @param array<string, mixed> $payload */
    private function isBranchDeleted(Request $request, array $payload): bool
    {
        if ($this->isGitHubRequest($request)) {
            return ($payload['ref_type'] ?? '') === 'branch';
        }

        return ($payload['after'] ?? '') === '0000000000000000000000000000000000000000';
    }

    /** @param array<string, mixed> $payload */
    private function extractBranch(array $payload): string
    {
        $ref = $payload['ref'] ?? '';

        return str_replace('refs/heads/', '', $ref);
    }
}
