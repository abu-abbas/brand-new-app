<?php

use App\Enums\PermissionType;

it('provides permission type options from enum metadata', function () {
    expect(PermissionType::options())->toBe([
        ['value' => 'menu', 'label' => 'Menu Sidebar'],
        ['value' => 'crud', 'label' => 'Aksi CRUD'],
        ['value' => 'filter', 'label' => 'Filter Data'],
    ]);
});
