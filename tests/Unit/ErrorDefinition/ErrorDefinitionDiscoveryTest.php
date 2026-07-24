<?php

use App\Core\ErrorDefinition\DiscoveryResult;
use App\Core\ErrorDefinition\ErrorDefinitionDiscovery;
use App\Core\ErrorDefinition\Exceptions\DiscoveryException;
use App\Core\ErrorDefinition\Traits\HasErrorDefinitions;
use App\Errors\UserManagementError;
use App\Http\Requests\User\ListUserRequest;
use Illuminate\Support\Facades\File;

it('discovers ErrorCode enums and FormRequests from application root autoload', function () {
    $discovery = new ErrorDefinitionDiscovery();
    $result = $discovery->discover();

    expect($result)->toBeInstanceOf(DiscoveryResult::class)
        ->and($result->errorEnums)->toBeArray()
        ->and($result->formRequests)->toBeArray();

    expect($result->errorEnums)->toContain(UserManagementError::class);
    expect($result->formRequests)->toContain(ListUserRequest::class);
});

it('uses in-memory snapshot for subsequent calls within the same instance', function () {
    $discovery = new ErrorDefinitionDiscovery();
    $firstResult = $discovery->discover();
    $secondResult = $discovery->discover();

    expect($firstResult)->toBe($secondResult);
});

it('ensures results are unique, sorted ascending, and contain only string FQCNs', function () {
    $discovery = new ErrorDefinitionDiscovery();
    $result = $discovery->discover();

    $sortedEnums = $result->errorEnums;
    sort($sortedEnums, SORT_STRING);
    expect($result->errorEnums)->toBe($sortedEnums)
        ->and(count($result->errorEnums))->toBe(count(array_unique($result->errorEnums)));

    $sortedRequests = $result->formRequests;
    sort($sortedRequests, SORT_STRING);
    expect($result->formRequests)->toBe($sortedRequests)
        ->and(count($result->formRequests))->toBe(count(array_unique($result->formRequests)));
});

it('throws DiscoveryException when root composer.json is missing or invalid', function () {
    $tempDir = storage_path('framework/testing/test_discovery_missing_' . uniqid());
    File::makeDirectory($tempDir, 0755, true);

    try {
        $discovery = new ErrorDefinitionDiscovery($tempDir);
        $discovery->discover();
    } finally {
        File::deleteDirectory($tempDir);
    }
})->throws(DiscoveryException::class);

it('throws DiscoveryException when an autoload directory does not exist or is unreadable', function () {
    $tempDir = storage_path('framework/testing/test_discovery_bad_path_' . uniqid());
    File::makeDirectory($tempDir, 0755, true);

    $composerContent = json_encode([
        'autoload' => [
            'psr-4' => [
                'NonExistent\\' => 'non_existent_folder/'
            ]
        ]
    ]);
    File::put($tempDir . '/composer.json', $composerContent);

    try {
        $discovery = new ErrorDefinitionDiscovery($tempDir);
        $discovery->discover();
    } finally {
        File::deleteDirectory($tempDir);
    }
})->throws(DiscoveryException::class);
