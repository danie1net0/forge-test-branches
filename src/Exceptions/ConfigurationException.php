<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Exceptions;

class ConfigurationException extends ForgeTestBranchesException
{
    public static function missingToken(): self
    {
        return new self('Forge API token not configured. Set FORGE_API_TOKEN in your .env file.');
    }

    public static function missingOrganization(): self
    {
        return new self('Forge organization not configured. Set FORGE_ORGANIZATION in your .env file.');
    }

    public static function missingServerId(): self
    {
        return new self('Forge server ID not configured. Set FORGE_SERVER_ID in your .env file.');
    }

    public static function invalidProjectType(string $value): self
    {
        return new self("Invalid site type \"{$value}\" for FORGE_PROJECT_TYPE. See the Forge API v2 documentation for supported values.");
    }

    public static function invalidGitProvider(string $value): self
    {
        return new self("Invalid git provider \"{$value}\" for FORGE_GIT_PROVIDER. Supported values: gitlab, github, bitbucket.");
    }

    public static function missingRepository(): self
    {
        return new self('Git repository not configured. Set FORGE_GIT_REPOSITORY (or CI_PROJECT_PATH) in your .env file.');
    }
}
