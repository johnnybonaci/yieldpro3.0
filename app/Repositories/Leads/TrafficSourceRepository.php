<?php

namespace App\Repositories\Leads;

use App\Traits\SavesWithResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Models\Leads\TrafficSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Contracts\SettingsRepositoryInterface;
use App\Interfaces\Leads\ImportRepositoryInterface;

class TrafficSourceRepository implements ImportRepositoryInterface, SettingsRepositoryInterface
{
    use SavesWithResponse;
    /**
     * Summary of create.
     */
    public function create(Collection $data, int $quantity): int
    {
        $data->chunk($quantity)->each(fn ($chunk) => TrafficSource::upsert($chunk->toArray(), ['id'], ['name', 'traffic_source_provider_id']));

        return $data->count();
    }

    /**
     * Summary of Resource.
     */
    public function resource(array $data, int $provider): array
    {
        return
            [
                $data['user_traffic_source_id'] => [
                    'id' => $data['user_traffic_source_id'] . $provider,
                    'name' => $data['company_name'],
                    'traffic_source_provider_id' => $data['id'],
                    'provider_id' => $provider,
                    'created_at' => now(),
                ],
            ];
    }

    /**
     * Summary of getById.
     */
    public function getById(int $traffic_source_id, int $provider): int
    {
        return TrafficSource::where('traffic_source_provider_id', $traffic_source_id)
            ->where('provider_id', $provider)
            ->firstOr(fn () => new TrafficSource())
            ->id;
    }

    public function getTrafficSource(): Builder
    {
        return TrafficSource::query();
    }

    /**
     * Implementation of SettingsRepositoryInterface.
     */
    public function getQuery(): Builder
    {
        return $this->getTrafficSource();
    }

    /**
     * Implementation of SettingsRepositoryInterface.
     */
    public function save(Request $request, Model $model): array
    {
        return $this->saveTrafficSource($request, $model);
    }

    /**
     * Implementation of SettingsRepositoryInterface.
     */
    public function getDefaultSortField(): string
    {
        return 'updated_at';
    }

    public function saveTrafficSource(Request $request, TrafficSource $traffic_source): array
    {
        return $this->saveWithResponse($traffic_source, 'Traffic Source', function ($model) use ($request) {
            $model->name = $request->get('name');
            $model->traffic_source_provider_id = $request->get('traffic_source_provider_id');
            $model->provider_id = $request->get('provider_id');
        });
    }

    public function show(): array
    {
        return TrafficSource::pluck('name', 'id')->toArray();
    }
}
