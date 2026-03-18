<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Http\Middleware;

use Closure;
use Ddr\ForgeTestBranches\Logger;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    public function __construct(
        private readonly Logger $logger,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('forge-test-branches.webhook.secret');

        if (! $secret) {
            return $next($request);
        }

        if ($this->isGitHubRequest($request)) {
            return $this->verifyGitHubSignature($request, $next, $secret);
        }

        return $this->verifyGitLabToken($request, $next, $secret);
    }

    protected function isGitHubRequest(Request $request): bool
    {
        return $request->hasHeader('X-GitHub-Event');
    }

    protected function verifyGitLabToken(Request $request, Closure $next, string $secret): Response
    {
        $token = $request->header('X-Gitlab-Token');

        if ($token !== $secret) {
            $this->logger->warning('Webhook rejected: invalid GitLab token');
            abort(401, 'Invalid webhook token');
        }

        return $next($request);
    }

    protected function verifyGitHubSignature(Request $request, Closure $next, string $secret): Response
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (! $signature) {
            $this->logger->warning('Webhook rejected: missing GitHub signature');
            abort(401, 'Missing webhook signature');
        }

        $payload = $request->getContent();
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        if (! hash_equals($expectedSignature, $signature)) {
            $this->logger->warning('Webhook rejected: invalid GitHub signature');
            abort(401, 'Invalid webhook signature');
        }

        return $next($request);
    }
}
