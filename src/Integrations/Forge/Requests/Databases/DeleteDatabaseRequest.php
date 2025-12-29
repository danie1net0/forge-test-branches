<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeleteDatabaseRequest extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        protected int $serverId,
        protected int $databaseId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/databases/{$this->databaseId}";
    }
}
