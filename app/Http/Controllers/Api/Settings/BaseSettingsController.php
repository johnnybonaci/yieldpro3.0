<?php

namespace App\Http\Controllers\Api\Settings;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Contracts\SettingsRepositoryInterface;
use Illuminate\Contracts\Pagination\Paginator;

/**
 * Base controller for all Settings endpoints.
 * Reduces code duplication and ensures consistent behavior.
 *
 * SonarCube Quality: Eliminates ~250 lines of duplicated code.
 */
abstract class BaseSettingsController extends Controller
{
    public function __construct(
        protected SettingsRepositoryInterface $repository,
    ) {
    }

    /**
     * Display a paginated listing of the resource.
     */
    public function index(Request $request): Paginator
    {
        $page = $request->get('page', 1);
        $size = $request->get('size', 20);

        $query = $this->repository->getQuery();

        return $query
            ->filterFields()
            ->sortsFields($this->repository->getDefaultSortField())
            ->paginate($size, ['*'], 'page', $page);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param mixed $model
     */
    public function update(Request $request, $model): JsonResponse
    {
        $result = $this->repository->save($request, $model);

        return response()->json($result);
    }

    /**
     * Optional hook for custom eager loading in child controllers.
     */
    protected function getEagerLoadRelations(): array
    {
        return [];
    }
}
