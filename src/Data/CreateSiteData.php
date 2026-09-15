<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Data;

use Override;
use Spatie\LaravelData\Attributes\{MapInputName, MapOutputName};
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
class CreateSiteData extends Data
{
    public function __construct(
        public string $name,
        public string $type,
        public string $domainMode = 'custom',
        // The OpenAPI spec marks these optional, but the API rejects site creation without them
        // for a custom domain — the only mode this package uses, so the default applies unconditionally.
        public string $wwwRedirectType = 'none',
        public bool $allowWildcardSubdomains = false,
        public ?string $webDirectory = null,
        public ?bool $isIsolated = null,
        public ?string $phpVersion = null,
        public ?string $sourceControlProvider = null,
        public ?string $repository = null,
        public ?string $branch = null,
        public ?bool $installComposerDependencies = null,
        /**
         * The default deploy script this package generates does not use the
         * `$CREATE_RELEASE()` / `$ACTIVATE_RELEASE()` macros zero-downtime
         * deployments require, so it must be explicitly disabled: Forge
         * enables it by default for every new site.
         */
        public bool $zeroDowntimeDeployments = false,
        public ?int $nginxTemplateId = null,
    ) {
    }

    /** @return array<string, mixed> */
    #[Override]
    public function toArray(): array
    {
        return array_filter(parent::toArray(), fn (mixed $value): bool => $value !== null);
    }
}
