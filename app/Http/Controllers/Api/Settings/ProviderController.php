<?php

namespace App\Http\Controllers\Api\Settings;

use Illuminate\Http\Request;
use App\Models\Leads\Provider;
use App\Http\Controllers\Controller;
use App\Repositories\Leads\ProviderRepository;
use Illuminate\Contracts\Pagination\Paginator;

class ProviderController extends Controller
{
    public function __construct(
        protected ProviderRepository $provider_repository,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Paginator
    {
        $page = $request->get('page', 1);
        $size = $request->get('size', 20);
        $rows = $this->provider_repository->getProvider();

        return $rows->filterFields()->sortsFields('id')->paginate($size, ['*'], 'page', $page);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Provider $provider)
    {
        return json_encode($this->provider_repository->saveProvider($request, $provider));
    }
}
