<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Data;

use Ddr\ForgeTestBranches\Data\Contracts\HasNameAttribute;
use Ddr\ForgeTestBranches\Integrations\Forge\Enums\{RepositoryStatus, SiteStatus};
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class SiteData extends Data implements HasNameAttribute
{
    public function __construct(
        public int $id,
        public int $serverId,
        public string $name,
        public string $status,
        public ?string $url = null,
        public ?string $user = null,
        public ?string $webDirectory = null,
        public ?string $phpVersion = null,
        public ?string $deploymentStatus = null,
        public ?bool $quickDeploy = null,
        public bool $isolated = false,
        #[MapInputName('repository.provider')]
        public ?string $repositoryProvider = null,
        #[MapInputName('repository.url')]
        public ?string $repositoryUrl = null,
        #[MapInputName('repository.branch')]
        public ?string $repositoryBranch = null,
        #[MapInputName('repository.status')]
        public ?string $repositoryStatus = null,
        public ?string $createdAt = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Whether the site and its repository finished installing. Deliberately
     * ignores the deployment status: the initial deploy triggered right
     * after installation can legitimately take longer than the install
     * itself, and should not block the environment from being considered
     * ready.
     */
    public function isInstalled(): bool
    {
        $status = SiteStatus::tryFrom($this->status);

        if ($status === null || $status->isPending() || $status->hasFailed()) {
            return false;
        }

        return $this->repositoryStatus === RepositoryStatus::INSTALLED->value;
    }

    public function hasFailedInstallation(): bool
    {
        return SiteStatus::tryFrom($this->status)?->hasFailed() ?? false;
    }

    public function isBeingRemoved(): bool
    {
        return SiteStatus::tryFrom($this->status)?->isBeingRemoved() ?? false;
    }
}
