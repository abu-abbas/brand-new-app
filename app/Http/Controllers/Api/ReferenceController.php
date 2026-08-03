<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReferenceOptionResource;
use App\Services\ReferenceService;
use Illuminate\Http\Request;

class ReferenceController extends Controller
{
    public function __construct(
        private readonly ReferenceService $referenceService,
    ) {}

    /**
     * @summary Mengambil daftar wilayah untuk opsi role/scope.
     *
     * @return array{data: list<array{code: string, name: string, order: int}>}
     */
    public function wilayah(Request $request): array
    {
        return [
            'data' => ReferenceOptionResource::collection(
                $this->referenceService->getWilayahOptions($request->user())
            )->resolve($request),
        ];
    }

    /**
     * @summary Mengambil daftar perangkat daerah untuk opsi role/scope.
     *
     * @return array{data: list<array{code: string, name: string, spmu: string, sipkd_code: string}>}
     */
    public function perangkatDaerah(Request $request): array
    {
        return [
            'data' => ReferenceOptionResource::collection(
                $this->referenceService->getPerangkatDaerahOptions($request->user())
            )->resolve($request),
        ];
    }
}
