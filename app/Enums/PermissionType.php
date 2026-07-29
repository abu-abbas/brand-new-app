<?php

namespace App\Enums;

use App\Attributes\PermissionTypeDefinition;
use ReflectionEnumUnitCase;

enum PermissionType: string
{
    #[PermissionTypeDefinition(label: 'Menu Sidebar')]
    case MENU = 'menu';

    #[PermissionTypeDefinition(label: 'Aksi CRUD')]
    case CRUD = 'crud';

    #[PermissionTypeDefinition(label: 'Filter Data')]
    case FILTER = 'filter';

    public function label(): string
    {
        return (new ReflectionEnumUnitCase(self::class, $this->name))
            ->getAttributes(PermissionTypeDefinition::class)[0]
            ->newInstance()
            ->label;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ],
            self::cases(),
        );
    }
}
