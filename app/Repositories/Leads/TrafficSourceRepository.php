<?php

namespace App\Repositories\Leads;

use App\Traits\SavesWithResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Models\Leads\TrafficSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Interfaces\Leads\ImportRepositoryInterface;

class TrafficSourceRepository extends AbstractSettingsRepository implements ImportRepositoryInterface
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
        return $this->getSettingsQuery();
    }

    protected function getSettingsQuery(): Builder
    {
        return TrafficSource::query();
    }

    protected function getDefaultSort(): string
    {
        return 'updated_at';
    }

    protected function saveSettings(Request $request, Model $traffic_source): array
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
