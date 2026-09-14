<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Concerns;

use Ddr\ForgeTestBranches\Exceptions\InvalidApiResponseException;
use Saloon\Http\Response;
use Spatie\LaravelData\Data;

trait ParsesJsonApiResponses
{
    /**
     * @template TResponseData of Data
     *
     * @param class-string<TResponseData> $dataClass
     * @param array<string, mixed> $extraAttributes
     * @return TResponseData
     */
    protected function dtoFromResponse(Response $response, string $dataClass, array $extraAttributes = []): Data
    {
        return $dataClass::from($this->resourceFromResponse($response, $extraAttributes));
    }

    /**
     * @param array<string, mixed> $extraAttributes
     * @return array<string, mixed>
     */
    protected function resourceFromResponse(Response $response, array $extraAttributes = []): array
    {
        $data = $response->json('data');

        if (! is_array($data)) {
            throw InvalidApiResponseException::missingDataField();
        }

        /** @var array<string, mixed> $data */
        return $this->flattenResource($data, $extraAttributes);
    }

    /**
     * @param array<string, mixed> $extraAttributes
     * @return array<int, array<string, mixed>>
     */
    protected function collectionFromResponse(Response $response, array $extraAttributes = []): array
    {
        $resources = $response->json('data');

        if (! is_array($resources)) {
            throw InvalidApiResponseException::missingDataField();
        }

        /** @var array<int, array<string, mixed>> $resources */
        return array_map(
            fn (array $resource): array => $this->flattenResource($resource, $extraAttributes),
            $resources,
        );
    }

    protected function stringAttributeFromResponse(Response $response, string $key): string
    {
        return (string) $response->json("data.attributes.{$key}");
    }

    /**
     * @param array<string, mixed> $resource
     * @param array<string, mixed> $extraAttributes
     * @return array<string, mixed>
     */
    private function flattenResource(array $resource, array $extraAttributes): array
    {
        if (! isset($resource['id']) || ! is_numeric($resource['id'])) {
            throw InvalidApiResponseException::missingIdField();
        }

        /** @var array<string, mixed> $attributes */
        $attributes = $resource['attributes'] ?? [];

        return [
            ...$attributes,
            ...$extraAttributes,
            'id' => (int) $resource['id'],
        ];
    }
}
