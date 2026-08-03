<?php

namespace App\Contracts;

use App\Core\ErrorDefinition\ErrorCode;
use BackedEnum;

interface HasNotFoundError
{
    public static function notFoundError(): ErrorCode&BackedEnum;
}
