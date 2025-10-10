<?php

namespace App\Http\Controllers\Api\Settings;

use App\Models\Leads\Pub;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\Leads\PubRepository;

class PubsController extends Controller
{
    public function __construct(
        protected PubRepository $pub_repository,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): void
    {
    }

    /**
     * Create the specified resource in storage.
     */
    public function create(Request $request): void
    {
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pub $pub)
    {
        return json_encode($this->pub_repository->savePubsOffer($request, $pub));
    }
}
