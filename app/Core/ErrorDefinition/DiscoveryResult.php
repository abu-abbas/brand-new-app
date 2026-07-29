<?php

namespace App\Core\ErrorDefinition;

use Illuminate\Foundation\Http\FormRequest;

final readonly class DiscoveryResult
{
    /**
     * @param  list<class-string<ErrorCode>>  $errorEnums
     * @param  list<class-string<FormRequest>>  $formRequests
     */
    public function __construct(
        public array $errorEnums,
        public array $formRequests,
    ) {}
}
