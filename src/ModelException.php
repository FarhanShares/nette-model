<?php

declare(strict_types=1);


namespace Farhanianz/NetteModel;

use Exception;

/**
 * Repository Exceptions.
 */

class ModelException extends Exception
{
    public static function forNoPrimaryKey(string $modelName): ModelException
    {
        return new static(sprintf('No Primary Key set in %s Table.', $modelName));
    }

    public static function forNoDateFormat(string $modelName): ModelException
    {
        return new static(sprintf('No DateTime modifier set in %s Repository.', $modelName));
    }

    public static function forInvalidAllowedFields(string $modelName): ModelException
    {
        return new static(sprintf('Allowed fields set in %s Repository is/are invalid.', $modelName));
    }

    public static function forEmptyDataset(string $modelName): ModelException
    {
        return new static(sprintf('Empty dataset insertion in %s Repository is invalid.', $modelName));
    }
}
