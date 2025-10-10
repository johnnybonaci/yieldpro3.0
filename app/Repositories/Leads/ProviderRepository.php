<?php

namespace App\Repositories\Leads;

use Illuminate\Http\Request;
use App\Models\Leads\Provider;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class ProviderRepository
{
    public function __construct()
    {
    }

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

    public function getProviders(): Builder
    {
        return Provider::query();
    }

    public function show(): array
    {
        return Provider::pluck('name', 'id')->toArray();
    }

    public function getProvider(): Builder
    {
        return Provider::query();
    }

    public function saveProvider(Request $request, Provider $provider): array
    {
        $icon = 'success';
        $message = 'The Provider has been successfully updated';
        $provider->name = $request->get('name') ?? '';
        $provider->service = $request->get('service') ?? '';
        $provider->url = $request->get('url') ?? '';
        $provider->active = $request->get('active');
        if (!empty($request->get('api_key'))) {
            $provider->api_key = __toHash($request->get('api_key'));
        }

        $provider->updated_at = now();

        $save = $provider->save();
        if (!$save) {
            $icon = 'error';
            $message = 'The Provider has not been updated';
        }
        $response = [
            'icon' => $icon,
            'message' => $message,
            'response' => $save,
        ];

        return $response;
    }
}
