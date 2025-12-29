<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('forge-test-branches.webhook.secret');

        if (! $secret) {
            return $next($request);
        }

        $token = $request->header('X-Gitlab-Token');

        if ($token !== $secret) {
            abort(401, 'Invalid webhook token');
        }

        return $next($request);
    }
}
