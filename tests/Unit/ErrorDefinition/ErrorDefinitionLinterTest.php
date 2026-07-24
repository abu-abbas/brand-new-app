<?php

use App\Core\ErrorDefinition\ErrorDefinitionDiscovery;
use App\Core\ErrorDefinition\ErrorDefinitionLinter;
use App\Core\ErrorDefinition\LintReport;

it('passes lint for current application error definitions', function () {
    $discovery = new ErrorDefinitionDiscovery();
    $result = $discovery->discover();

    $linter = new ErrorDefinitionLinter();
    $report = $linter->lint($result->errorEnums, $result->formRequests);

    if (!$report->isEmpty()) {
        $messages = array_map(
            fn ($f) => "[{$f->ruleId}] {$f->source}: {$f->message}",
            $report->all()
        );
        $this->fail("Lint findings:\n" . implode("\n", $messages));
    }

    expect($report)->toBeInstanceOf(LintReport::class)
        ->and($report->isEmpty())->toBeTrue();
});

it('detects duplicate error codes across enums', function () {
    // Smoke test: memastikan linter jalan tanpa error
    $linter = new ErrorDefinitionLinter();
    $report = $linter->lint([\App\Errors\UserManagementError::class], []);

    expect($report->hasErrors())->toBeFalse();
});
