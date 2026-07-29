<?php

use App\Core\ErrorDefinition\Exceptions\ErrorValidationException;
use Illuminate\Routing\Redirector;
use Illuminate\Validation\Validator;
use Tests\Fixtures\ErrorDefinition\ManualValidationTestRequest;

it('merges addValidationError() entries from after() with rule-based failures, keeps the real validator, and interpolates message placeholders', function () {
    $request = ManualValidationTestRequest::create('/test', 'POST', [
        'username' => 'sudah_dipakai',
    ]);
    $request->setContainer(app())->setRedirector(app(Redirector::class));

    try {
        $request->validateResolved();
        $this->fail('Expected ErrorValidationException to be thrown.');
    } catch (ErrorValidationException $e) {
        $structured = $e->structuredErrors();

        // Manual error dari addValidationError() tidak boleh hilang diam-diam.
        expect($structured)->toHaveKey('field_uji')
            ->and($structured)->toHaveKey('username')
            ->and($structured['username'][0])->toBe([
                'code' => 'TEST-VAL-101',
                'message' => 'Username sudah digunakan.',
                'retryable' => false,
            ]);

        // Placeholder :attribute harus terinterpolasi, bukan template mentah.
        expect($structured['field_uji'][0]['message'])->toBe('field uji wajib diisi.');

        // Validator asli (dengan data request sesungguhnya) harus tetap dipakai, bukan stub kosong.
        expect($e->validator)->toBeInstanceOf(Validator::class)
            ->and($e->validator->getData())->toHaveKey('username', 'sudah_dipakai');
    }
});
