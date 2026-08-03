<?php

namespace App\Http\Controllers\Api;

use App\Core\ErrorDefinition\ErrorDefinitionReader;
use App\Core\ErrorDefinition\Exceptions\ApplicationException;
use App\Errors\AuthError;
use App\Http\Controllers\Controller;
use App\Http\Requests\Feature\ListFeatureRequest;
use App\Http\Requests\Feature\StoreFeatureRequest;
use App\Http\Resources\FeatureOptionResource;
use App\Http\Resources\FeatureResource;
use App\Models\Feature;
use App\Services\FeatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class FeatureController extends Controller
{
    public function __construct(private readonly FeatureService $featureService) {}

    /**
     * Display a paginated list of features, optionally including soft-deleted records.
     *
     * @summary Mengambil daftar feature ter-paginasi.
     */
    public function index(ListFeatureRequest $request): AnonymousResourceCollection
    {
        $user = $request->user();
        if ($user && ! $user->isRoot() && ! $user->can('manajemen-fitur') && ! $user->can('manajemen-group') && $request->input('type') !== 'menu') {
            $reader = new ErrorDefinitionReader;
            throw new ApplicationException($reader->read(AuthError::UNAUTHORIZED_ACTION));
        }

        return FeatureResource::collection(
            $this->featureService->paginate($request->validated())
        );
    }

    /**
     * Display active features for parent selection.
     *
     * @summary Mengambil pilihan fitur induk.
     */
    public function options(): AnonymousResourceCollection
    {
        return FeatureOptionResource::collection($this->featureService->options());
    }

    /**
     * Store a new feature.
     *
     * @summary Menambah fitur baru.
     */
    public function store(StoreFeatureRequest $request): JsonResponse
    {
        return FeatureResource::make(
            $this->featureService->create($request->validated())
        )->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update an existing feature.
     *
     * @summary Memperbarui fitur.
     */
    public function update(StoreFeatureRequest $request, Feature $feature): FeatureResource
    {
        return FeatureResource::make(
            $this->featureService->update($feature, $request->validated())
        );
    }

    /**
     * Soft-delete an existing feature.
     *
     * @summary Menghapus fitur.
     */
    public function destroy(Feature $feature): Response
    {
        $this->featureService->delete($feature);

        return response()->noContent();
    }

    /**
     * Restore a soft-deleted feature.
     *
     * @summary Memulihkan fitur terhapus.
     */
    public function restore(Feature $feature): FeatureResource
    {
        return FeatureResource::make($this->featureService->restore($feature));
    }
}
