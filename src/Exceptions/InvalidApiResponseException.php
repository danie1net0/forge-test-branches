<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Exceptions;

class InvalidApiResponseException extends ForgeTestBranchesException
{
    public static function missingDataField(): self
    {
        return new self('The Forge API response is missing the expected "data" field.');
    }

    public static function missingIdField(): self
    {
        return new self('The Forge API response is missing the expected "id" field.');
    }
}
