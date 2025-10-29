<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Exceptions;

use CodebarAg\MFiles\DTO\MFilesError;
use Exception as BaseException;

final class MFilesErrorException extends BaseException
{
    public function __construct(
        public readonly MFilesError $error
    ) {
        parent::__construct($error->exceptionMessage, $error->status);
    }
}
