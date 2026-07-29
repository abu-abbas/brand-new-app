<?php

namespace App\Console\Commands;

use App\Core\ErrorDefinition\ErrorDefinitionDiscovery;
use App\Core\ErrorDefinition\ErrorDefinitionLinter;
use Illuminate\Console\Command;

class LintErrorDefinitionCommand extends Command
{
    protected $signature = 'error-definition:lint
        {--strict : Treat warnings as errors (fail on warnings)}';

    protected $description = 'Validate ErrorDefinition enums and FormRequest mappings';

    public function handle(): int
    {
        $this->info('Discovering ErrorDefinition targets...');

        $discovery = new ErrorDefinitionDiscovery;
        $result = $discovery->discover();

        $this->info(sprintf(
            'Found %d error enum(s) and %d FormRequest(s).',
            count($result->errorEnums),
            count($result->formRequests),
        ));

        $linter = new ErrorDefinitionLinter;
        $report = $linter->lint($result->errorEnums, $result->formRequests);

        if ($report->isEmpty()) {
            $this->info('✅ No issues found. All error definitions and mappings are valid.');

            return 0;
        }

        // Display findings
        foreach ($report->errors() as $finding) {
            $this->error("[{$finding->ruleId}] {$finding->source}: {$finding->message}");
        }

        foreach ($report->warnings() as $finding) {
            $this->warn("[{$finding->ruleId}] {$finding->source}: {$finding->message}");
        }

        $errorCount = count($report->errors());
        $warningCount = count($report->warnings());

        $this->newLine();
        $this->info("Summary: {$errorCount} error(s), {$warningCount} warning(s).");

        if ($report->hasErrors()) {
            $this->error('❌ Lint failed with errors.');

            return 1;
        }

        if ($this->option('strict') && $report->hasWarnings()) {
            $this->error('❌ Lint failed in strict mode (warnings treated as errors).');

            return 1;
        }

        $this->warn('⚠️ Lint passed with warnings.');

        return 0;
    }
}
