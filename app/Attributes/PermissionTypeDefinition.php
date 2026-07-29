<?php

namespace App\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
final readonly class PermissionTypeDefinition
{
    public function __construct(
        public string $label,
    ) {}
}
