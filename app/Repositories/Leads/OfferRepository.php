<?php

namespace App\Repositories\Leads;

use App\Traits\SavesWithResponse;
use App\Models\Leads\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Contracts\SettingsRepositoryInterface;
use App\Interfaces\Leads\ImportRepositoryInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class OfferRepository implements ImportRepositoryInterface, SettingsRepositoryInterface
{
    use SavesWithResponse;
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

    /**
     * Implementation of SettingsRepositoryInterface.
     */
    public function getQuery(): Builder
    {
        return $this->getOffers();
    }

    /**
     * Implementation of SettingsRepositoryInterface.
     */
    public function save(Request $request, Model $model): array
    {
        return $this->saveOffers($request, $model);
    }

    /**
     * Implementation of SettingsRepositoryInterface.
     */
    public function getDefaultSortField(): string
    {
        return 'id';
    }

    public function show(): array
    {
        return Offer::pluck('type', 'id')->toArray();
    }

    public function saveOffers(Request $request, Offer $offer): array
    {
        return $this->saveWithResponse($offer, 'offer', function ($model) use ($request) {
            $model->name = $request->get('name');
            $model->type = $request->get('type');
            $model->source_url = $request->get('source_url');
            $model->provider_id = $request->get('provider_id');
            $api_key = $request->get('api_key');
            if (!empty($api_key)) {
                $model->api_key = Hash::make($api_key);
            }
        });
    }
}
