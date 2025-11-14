<?php

namespace App\Repositories\Leads;

use App\Traits\SettingsRepositoryImplementation;
use App\Contracts\SettingsRepositoryInterface;

/**
 * Abstract Settings Repository - Eliminates Boilerplate
 *
 * Consolidates SettingsRepositoryInterface implementation from 6 repositories:
 * - BuyerRepository
 * - ProviderRepository
 * - TrafficSourceRepository
 * - DidNumberRepository
 * - OfferRepository
 * - PhoneRoomRepository
 *
 * Reduces ~45 lines of duplicated boilerplate code.
 *
 * Each repository only needs to implement:
 * - getSettingsQuery(): Builder
 * - saveSettings(Request $request, Model $model): array
 * - getDefaultSort(): string (optional, defaults to 'id')
 */
abstract class AbstractSettingsRepository implements SettingsRepositoryInterface
{
    use SettingsRepositoryImplementation;
}
