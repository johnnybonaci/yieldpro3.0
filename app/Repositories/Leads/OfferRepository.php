<?php

namespace App\Repositories\Leads;

use App\Models\Leads\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Builder;
use App\Interfaces\Leads\ImportRepositoryInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class OfferRepository implements ImportRepositoryInterface
{
    public function __construct()
    {
    }

    /**
     * Summary of create.
     */
    public function create(Collection $data, int $quantity): int
    {
        $data->chunk($quantity)->each(fn ($chunk) => Offer::upsert($chunk->toArray(), ['id'], ['name']));

        return $data->count();
    }

    /**
     * Summary of Resource.
     */
    public function resource(array $data, int $provider): array
    {
        return
            [
                $data['user_offer_id'] => [
                    'id' => $data['user_offer_id'] . $provider,
                    'name' => $data['name'],
                    'offer_provider_id' => $data['id'],
                    'provider_id' => $provider,
                    'created_at' => now(),
                ],
            ];
    }

    /**
     * Summary of getByNames.
     */
    public function getByNames(array $names, int $provider): EloquentCollection
    {
        return Offer::whereIn('name', $names)->where('provider_id', $provider)->get();
    }

    /**
     * Summary of getByNames.
     */
    public function getByType(array $names, int $provider): EloquentCollection
    {
        return Offer::whereIn('type', $names)->where('provider_id', $provider)->get();
    }

    /**
     * Summary of getById.
     */
    public function getById(int $offer_id, int $provider): ?int
    {
        return Offer::where('offer_provider_id', $offer_id)
            ->where('provider_id', $provider)
            ->firstOr(fn () => new Offer())
            ->id;
    }

    public function getOffers(): Builder
    {
        return Offer::query();
    }

    public function show(): array
    {
        return Offer::pluck('type', 'id')->toArray();
    }

    public function saveOffers(Request $request, Offer $offer): array
    {
        $icon = 'success';
        $message = 'The offer has been successfully updated';
        $offer->name = $request->get('name');
        $offer->type = $request->get('type');
        $offer->source_url = $request->get('source_url');
        $offer->provider_id = $request->get('provider_id');
        $api_key = $request->get('api_key');
        if (!empty($api_key)) {
            $offer->api_key = Hash::make($api_key);
        }

        $save = $offer->save();
        if (!$save) {
            $icon = 'error';
            $message = 'The offer has not been updated';
        }
        $response = [
            'icon' => $icon,
            'message' => $message,
            'response' => $save,
        ];

        return $response;
    }
}
