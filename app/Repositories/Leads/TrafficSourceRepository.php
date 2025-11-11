<?php

namespace App\Repositories\Leads;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Models\Leads\TrafficSource;
use Illuminate\Database\Eloquent\Builder;
use App\Interfaces\Leads\ImportRepositoryInterface;

class TrafficSourceRepository implements ImportRepositoryInterface
{
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

    public function saveTrafficSource(Request $request, TrafficSource $traffic_source): array
    {
        $icon = 'success';
        $message = 'The Traffic Source has been successfully updated';
        $traffic_source->name = $request->get('name');
        $traffic_source->traffic_source_provider_id = $request->get('traffic_source_provider_id');
        $traffic_source->provider_id = $request->get('provider_id');
        $traffic_source->updated_at = now();

        $save = $traffic_source->save();
        if (!$save) {
            $icon = 'error';
            $message = 'The Traffic Source has not been updated';
        }
        $response = [
            'icon' => $icon,
            'message' => $message,
            'response' => $save,
        ];

        return $response;
    }

    public function show(): array
    {
        return TrafficSource::pluck('name', 'id')->toArray();
    }
}
