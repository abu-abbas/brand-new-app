<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferenceMockController extends Controller
{
    public function __construct(
        private readonly ReferenceService $referenceService,
    ) {}

    /**
     * Mengambil data mock Wilayah.
     *
     * @summary Mengambil daftar wilayah mock untuk opsi dropdown penugasan role/scope.
     */
    public function wilayah(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->referenceService->getWilayahOptions($request->user()),
        ]);
    }

    /**
     * Mengambil data mock Perangkat Daerah (Unit).
     *
     * @summary Mengambil daftar perangkat daerah (unit) mock untuk opsi dropdown penugasan role/scope.
     */
    public function perangkatDaerah(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->referenceService->getPerangkatDaerahOptions($request->user()),
        ]);
    }
}
