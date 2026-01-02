<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge;

use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\{AcceptsJson, AlwaysThrowOnErrors};

class ForgeConnector extends Connector
{
    use AcceptsJson;
    use AlwaysThrowOnErrors;

    public function __construct(
        protected string $apiToken
    ) {
    }

    public function resolveBaseUrl(): string
    {
        return 'https://forge.laravel.com/api/v1';
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
