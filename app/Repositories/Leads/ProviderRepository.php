<?php

namespace App\Repositories\Leads;

use App\Traits\SavesWithResponse;
use Illuminate\Http\Request;
use App\Models\Leads\Provider;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Contracts\SettingsRepositoryInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class ProviderRepository implements SettingsRepositoryInterface
{
    use SavesWithResponse;
    /**
     * Summary of create.
     */
    public function create(Collection $data, int $quantity): int
    {
        $data->chunk($quantity)->each(fn ($chunk) => Provider::upsert($chunk->toArray(), ['id'], ['name']));

        return $data->count();
    }

    /**
     * Summary of getByNames.
     */
    public function getByNames(array $names): EloquentCollection
    {
        return Provider::whereIn('name', $names)->get();
    }

    public function show(): array
    {
        return Provider::pluck('name', 'id')->toArray();
    }

    public function getProvider(): Builder
    {
        return Provider::query();
    }

    /**
     * Implementation of SettingsRepositoryInterface.
     */
    public function getQuery(): Builder
    {
        return $this->getProvider();
    }

    /**
     * Implementation of SettingsRepositoryInterface.
     */
    public function save(Request $request, Model $model): array
    {
        return $this->saveProvider($request, $model);
    }

    /**
     * Implementation of SettingsRepositoryInterface.
     */
    public function getDefaultSortField(): string
    {
        return 'id';
    }

    public function saveProvider(Request $request, Provider $provider): array
    {
        return $this->saveWithResponse($provider, 'Provider', function ($model) use ($request) {
            $model->name = $request->get('name') ?? '';
            $model->service = $request->get('service') ?? '';
            $model->url = $request->get('url') ?? '';
            $model->active = $request->get('active');
            if (!empty($request->get('api_key'))) {
                $model->api_key = __toHash($request->get('api_key'));
            }
        });
    }
}
