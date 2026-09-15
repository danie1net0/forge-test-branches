<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Requests;

use Ddr\ForgeTestBranches\Integrations\Forge\Concerns\ParsesJsonApiResponses;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

/**
 * @template TItem
 */
abstract class PaginatedRequest extends Request
{
    use ParsesJsonApiResponses;

    protected Method $method = Method::GET;

    /** @return array<int, TItem> */
    public function createDtoFromResponse(Response $response): array
    {
        return array_map(
            $this->createItem(...),
            $this->collectionFromResponse($response, $this->parentAttributes()),
        );
    }

    public function withCursor(?string $cursor): static
    {
        if ($cursor === null) {
            $this->query()->remove('page[cursor]');

            return $this;
        }

        $this->query()->add('page[cursor]', $cursor);

        return $this;
    }

    /**
     * @param array<string, mixed> $attributes
     * @return TItem
     */
    abstract protected function createItem(array $attributes): mixed;

    /** @return array<string, mixed> */
    abstract protected function parentAttributes(): array;
}
