<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge;

use Ddr\ForgeTestBranches\Integrations\Forge\Requests\PaginatedRequest;
use Saloon\Enums\Method;
use Saloon\Exceptions\Request\Statuses\TooManyRequestsException;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\{Connector, Request as SaloonRequest};
use Saloon\Exceptions\Request\{FatalRequestException, RequestException};
use Saloon\Traits\Plugins\{AcceptsJson, AlwaysThrowOnErrors};
use Saloon\Traits\RequestProperties\HasTries;

class ForgeConnector extends Connector
{
    use AcceptsJson;
    use AlwaysThrowOnErrors;
    use HasTries;

    private const int MAX_PAGES = 100;

    public function __construct(
        protected string $apiToken,
        protected string $organization,
    ) {
        $this->tries = 3;
        $this->retryInterval = 1_000;
        $this->useExponentialBackoff = true;
    }

    public function resolveBaseUrl(): string
    {
        return "https://forge.laravel.com/api/orgs/{$this->organization}";
    }

    /**
     * @template TItem
     *
     * @param PaginatedRequest<TItem> $request
     * @return array<int, TItem>
     */
    public function sendPaginated(PaginatedRequest $request): array
    {
        $items = [];
        $seenCursors = [];
        $cursor = null;

        for ($page = 0; $page < self::MAX_PAGES; $page++) {
            $request->withCursor($cursor);
            $response = $this->send($request);
            $items = [...$items, ...$request->createDtoFromResponse($response)];
            $cursor = $response->json('meta.next_cursor');

            if (! is_string($cursor) || $cursor === '' || isset($seenCursors[$cursor])) {
                return $items;
            }

            $seenCursors[$cursor] = true;
        }

        return $items;
    }

    /**
     * Only retry rate-limited (429) and server-side (5xx or network-level)
     * failures. Client errors such as 404 or 422 are never transient and
     * should fail fast.
     *
     * POST is the only non-idempotent verb this API uses (it creates a
     * resource), so a 5xx or network failure there is ambiguous: the
     * request may have been processed before the response was lost.
     * Retrying could create a duplicate site, database or database user, so
     * POST is only retried on 429, which is rejected before any processing.
     */
    public function handleRetry(FatalRequestException|RequestException $exception, SaloonRequest $request): bool
    {
        if ($exception instanceof TooManyRequestsException) {
            return true;
        }

        if ($request->getMethod() === Method::POST) {
            return false;
        }

        if ($exception instanceof FatalRequestException) {
            return true;
        }

        return $exception->getStatus() >= 500;
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
        ];
    }

    protected function defaultAuth(): TokenAuthenticator
    {
        return new TokenAuthenticator($this->apiToken);
    }
}
