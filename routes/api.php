<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Leads\CallController;
use App\Http\Controllers\Api\Leads\LeadController;
use App\Http\Controllers\Api\Roles\RoleController;
use App\Http\Controllers\Api\Leads\JornayaController;
use App\Http\Controllers\Api\Leads\ListPubController;
use App\Http\Controllers\Api\Leads\ListSubController;
use App\Http\Controllers\Api\Settings\PubsController;
use App\Http\Controllers\Api\Leads\CampaignController;
use App\Http\Controllers\Api\Settings\BuyerController;
use App\Http\Controllers\Api\Settings\OfferController;
use App\Http\Controllers\Api\Settings\PubIdController;
use App\Http\Controllers\Api\Leads\PhoneRoomController;
use App\Http\Controllers\Api\Leads\MediaAlphaController;
use App\Http\Controllers\Api\Leads\ListPartnerController;
use App\Http\Controllers\Api\Leads\LeadPageViewController;
use App\Http\Controllers\Api\Leads\ListCampaignController;
use App\Http\Controllers\Api\Settings\DidNumberController;
use App\Http\Controllers\Api\Leads\MediaAlphaLeadController;
use App\Http\Controllers\Backend\Leads\AccessTokenController;
use App\Http\Controllers\Api\Settings\TrafficSourceController;
use App\Http\Controllers\Api\Leads\MediaAlphaResponseController;
use App\Http\Controllers\Api\Settings\MediaAlphaConfigController;
use App\Http\Controllers\Api\Users\UserController as UserApiController;
use App\Http\Controllers\Api\Settings\ProviderController as ProviderApiController;
use App\Http\Controllers\Api\Settings\PhoneRoomController as PhoneRoomApiController;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::get('user', [UserApiController::class, 'authenticated'])->name('auth.user');
    Route::get('roles', [RoleController::class, 'index'])->name('auth.roles.list');
    Route::middleware('auth:sanctum')->get('/auth/user/{userId}', [UserApiController::class, 'getUserById']);
});

Route::middleware(['auth:sanctum', 'abilities:lead.api:create,lead.api:read'])->get('/user', function (Request $request) {
    return $request->user();
});
/*
|--------------------------------------------------------------------------
| Access Token
|--------------------------------------------------------------------------
|
*/
Route::post('token', [AccessTokenController::class, 'token'])->name('auth.token');

/*
|--------------------------------------------------------------------------
| Data Routes - READ operations (from web.php)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->prefix('data')->group(function () {
    // Metadata
    Route::get('/', [CallController::class, 'metadata'])->name('lead.api.metadata');

    // Lists
    Route::get('partners', ListPartnerController::class)->name('lead.api.partners');
    Route::get('campaigns', ListCampaignController::class)->name('lead.api.campaigns');
    Route::get('subs', ListSubController::class)->name('lead.api.subs');
    Route::get('pubs', ListPubController::class)->name('lead.api.pubs');

    // Media Alpha responses
    Route::get('media-alpha', [MediaAlphaResponseController::class, 'responses'])->name('media-alpha.responses');

    // Leads
    Route::get('leads', [LeadController::class, 'index'])->name('lead.api.index');
    Route::get('leads-old', [LeadController::class, 'index_old'])->name('lead.api.index_old');
    Route::get('leads-new', [LeadController::class, 'index_new'])->name('lead.api.index_new');

    // History
    Route::get('history', [LeadController::class, 'history_leads'])->name('lead.api.history');
    Route::get('history-new', [LeadController::class, 'historyLeads'])->name('lead.api.historyNew');

    // Calls
    Route::get('calls', [CallController::class, 'index'])->name('call.api.index');
    Route::get('calls-old', [CallController::class, 'index_old'])->name('call.api.index_old');
    Route::get('calls-new', [CallController::class, 'index_new'])->name('call.api.index_new');

    // Reports and metrics
    Route::get('jornaya', [JornayaController::class, 'index'])->name('jornaya.api.index');
    Route::get('campaign', [CampaignController::class, 'index'])->name('lead.campaign.api.index');
    Route::get('campaign-mn', [CampaignController::class, 'campaign_mn'])->name('lead.campaign_mn.api.index');
    Route::get('users', [UserApiController::class, 'index'])->name('users.api.index');
    Route::get('phoneroom', [PhoneRoomController::class, 'index'])->name('phoneroom.api.index');
    Route::get('metricsphoneroom', [PhoneRoomController::class, 'metrics'])->name('phoneroom.api.metrics');
    Route::get('reportsphoneroom', [PhoneRoomController::class, 'reports'])->name('phoneroom.api.reports');
    Route::get('pageviews', [LeadPageViewController::class, 'index'])->name('lead.pageviews.api.index');
    Route::get('report-cpa', [CallController::class, 'reportCpa'])->name('lead.cpa.api.index');
    Route::get('report-rpc', [CallController::class, 'reportRpc'])->name('lead.rpc.api.index');
    Route::get('report-qa', [CallController::class, 'reportQa'])->name('lead.qa.api.index');

    // Call operations (moved from separate routes)
    Route::post('transcript', [CallController::class, 'transcript'])->name('call.api.process');
    Route::post('reprocess', [CallController::class, 'reprocess'])->name('call.api.reprocess');
    Route::post('edit', [CallController::class, 'edit'])->name('call.api.edit');
    Route::post('ask', [CallController::class, 'ask'])->name('call.api.ask');
    Route::post('makeread', [CallController::class, 'makeRead'])->name('call.api.makeread');

    Route::get('roles', [RoleController::class, 'indexApi'])->name('roles.list');
    Route::get('roles/{role}', [RoleController::class, 'show'])->name('roles.show');
    Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
    Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::get('permissions', [RoleController::class, 'permissions'])->name('permissions.list');

    // Settings routes (from web.php)
    Route::prefix('settings')->group(function () {
        Route::get('pubid', [PubIdController::class, 'index'])->name('pubid.api.index');
        Route::get('media-alpha', [MediaAlphaConfigController::class, 'index'])->name('medialpha.api.index');
        Route::get('offer', [OfferController::class, 'index'])->name('offer.api.index');
        Route::get('trafficsource', [TrafficSourceController::class, 'index'])->name('trafficsource.api.index');
        Route::get('buyer', [BuyerController::class, 'index'])->name('buyer.api.index');
        Route::get('did', [DidNumberController::class, 'index'])->name('did.api.index');
        Route::get('phoneroom', [PhoneRoomApiController::class, 'index'])->name('phoneroom.api.index');
        Route::get('provider', [ProviderApiController::class, 'index'])->name('provider.api.index');
        Route::get('pubsbyoffer/{offerid}', [PubIdController::class, 'pubsByOffer'])->name('pubidbyoffer.api.get');
    });
});

/*
|--------------------------------------------------------------------------
| API Leads - WRITE operations
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'abilities:lead.api:create,lead.api:read'])->prefix('leads')->group(function () {
    Route::post('data', [LeadController::class, 'store'])->name('lead.api.store');
    Route::post('/mediaalpha', [MediaAlphaController::class, 'submit'])->name('lead.api.mediaalpha');
    Route::post('update', [LeadController::class, 'update'])->name('lead.api.update');
});

/*
|--------------------------------------------------------------------------
| Media Alpha API
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'abilities:lead.api:create,lead.api:read'])->prefix('media-alpha')->group(function () {
    Route::prefix('configs')->group(function () {
        Route::get('/', [MediaAlphaConfigController::class, 'index']);
        Route::post('/', [MediaAlphaConfigController::class, 'store']);
        Route::get('/active', [MediaAlphaConfigController::class, 'active']);
        Route::get('/placement/{placementId}', [MediaAlphaConfigController::class, 'getByPlacementId']);
        Route::get('/{config}', [MediaAlphaConfigController::class, 'show']);
        Route::put('/{config}', [MediaAlphaConfigController::class, 'update']);
        Route::delete('/{config}', [MediaAlphaConfigController::class, 'destroy']);
        Route::patch('/{config}/toggle-active', [MediaAlphaConfigController::class, 'toggleActive']);
    });
    Route::prefix('responses')->group(function () {
        Route::get('/', [MediaAlphaResponseController::class, 'index'])
            ->name('media-alpha.responses.index');

        Route::get('/statistics', [MediaAlphaResponseController::class, 'statistics'])
            ->name('media-alpha.responses.statistics');

        Route::get('/{phone}', [MediaAlphaResponseController::class, 'show'])
            ->name('media-alpha.responses.show');
    });
    Route::prefix('leads')->group(function () {
        Route::post('/ping/{placementId}', [MediaAlphaLeadController::class, 'ping']);
        Route::post('/post/{placementId}', [MediaAlphaLeadController::class, 'post']);
        Route::post('/submit/{placementId}', [MediaAlphaLeadController::class, 'submit']);
    });
});

/*
|--------------------------------------------------------------------------
| API Calls
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'abilities:lead.api:create,lead.api:read'])->prefix('calls')->group(function () {
    Route::post('phoneroom', [PhoneRoomController::class, 'store'])->name('phoneroom.api.store');
});
/*
|--------------------------------------------------------------------------
| API Lead Page View
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'abilities:lead.api:create,lead.api:read'])->prefix('page')->group(function () {
    Route::post('views', [LeadPageViewController::class, 'store'])->name('pageviews.api.store');
});
/*
|--------------------------------------------------------------------------
| API PUBS
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'abilities:lead.api:create,lead.api:read'])->prefix('pubs')->group(function () {
    Route::post('update/{pubid}', [PubIdController::class, 'update'])->name('pubid.api.update');
    Route::post('create', [PubIdController::class, 'create'])->name('pubid.api.create');
});

/*
|--------------------------------------------------------------------------
| API PUBS By Offer
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'abilities:lead.api:create,lead.api:read'])->prefix('pubsoffer')->group(function () {
    Route::post('update/{pub?}', [PubsController::class, 'update'])->name('pubs.api.update');
});

/*
|--------------------------------------------------------------------------
| API OFFERS
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'abilities:lead.api:create,lead.api:read'])->prefix('offers')->group(function () {
    Route::post('update/{offer}', [OfferController::class, 'update'])->name('offer.api.update');
});

/*
|--------------------------------------------------------------------------
| API TRAFFIC SOURCE
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'abilities:lead.api:create,lead.api:read'])->prefix('trafficsource')->group(function () {
    Route::post('update/{traffic_source}', [TrafficSourceController::class, 'update'])->name('trafficsource.api.update');
});

/*
|--------------------------------------------------------------------------
| API BUYER
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'abilities:lead.api:create,lead.api:read'])->prefix('buyer')->group(function () {
    Route::post('update/{buyer}', [BuyerController::class, 'update'])->name('buyer.api.update');
    Route::post('selection/', [BuyerController::class, 'selection'])->name('buyer.api.selection');
});

/*
|--------------------------------------------------------------------------
| API DID NUMBER
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'abilities:lead.api:create,lead.api:read'])->prefix('did')->group(function () {
    Route::post('update/{did_number}', [DidNumberController::class, 'update'])->name('did.api.update');
});

/*
|--------------------------------------------------------------------------
| API PHONE ROOM
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'abilities:lead.api:create,lead.api:read'])->prefix('phoneroom')->group(function () {
    Route::post('update/{phone_room}', [PhoneRoomApiController::class, 'update'])->name('phoneroom.api.update');
});

/*
|--------------------------------------------------------------------------
| API PROVIDER
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'abilities:lead.api:create,lead.api:read'])->prefix('provider')->group(function () {
    Route::post('update/{provider}', [ProviderApiController::class, 'update'])->name('provider.api.update');
});

/*
|--------------------------------------------------------------------------
| API CALLS TRANSCRIPT
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'abilities:lead.api:create,lead.api:read'])->prefix('call')->group(function () {
    Route::post('transcript', [CallController::class, 'transcript'])->name('transcript.api.process');
    Route::post('reprocess', [CallController::class, 'reprocess'])->name('transcript.api.reprocess');
    Route::post('edit', [CallController::class, 'edit'])->name('transcript.api.edit');
    Route::post('ask', [CallController::class, 'ask'])->name('transcript.api.ask');
    Route::post('makeread', [CallController::class, 'makeRead'])->name('transcript.api.makeread');
});

/*
|--------------------------------------------------------------------------
| API TESTS
|--------------------------------------------------------------------------
*/
Route::post('test/calls/process', [App\Http\Controllers\Api\Tests\TestsController::class, 'process']);
Route::get('test/calls/search', [App\Http\Controllers\Api\Tests\TestsController::class, 'search']);
Route::get('test/calls/transcript', [App\Http\Controllers\Api\Tests\TestsController::class, 'transcript']);
Route::post('test/ai', [App\Http\Controllers\Api\Tests\TestsController::class, 'ai']);
Route::post('test/ai/ask', [App\Http\Controllers\Api\Tests\TestsController::class, 'ask']);
