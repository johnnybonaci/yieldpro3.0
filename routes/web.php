<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Cookie;
use App\Http\Controllers\Api\Leads\CallController;
use App\Http\Controllers\Api\Leads\LeadController;
use App\Http\Controllers\Api\Leads\CampaignController;
use App\Http\Controllers\Backend\Users\UserController;
use App\Http\Controllers\Api\Leads\LeadPageViewController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/sanctum/csrf-cookie', function (Request $request) {
    $cookieName = env('XSRF_COOKIE_NAME', 'XSRF-TOKEN');
    $config = config('session');

    return response()->noContent()->withCookie(new Cookie(
        $cookieName,
        $request->session()->token(),
        now()->addMinutes($config['lifetime'] ?? 120),
        $config['path'] ?? '/',
        $config['domain'] ?? null,
        $config['secure'] ?? true,
        false,
        false,
        $config['same_site'] ?? 'lax'
    ));
})->name('sanctum.csrf-cookie')->middleware('web');

Route::get('/', function () {
    $currentHost = request()->getHost();

    if ($currentHost === 'yieldpro.io') {
        return view('welcome');
    } elseif ($currentHost === 'vp.yieldpro.io') {
        return redirect()->away('https://vtp.yieldpro.io');
    } else {
        return redirect()->away('https://yieldpro.io');
    }
})->name('home');
Route::view('/privacy', 'privacy')->name('privacy');
Route::get('/login', function () {
    return response()->json([
        'message' => 'Please authenticate through the API',
    ], 401);
})->name('login');
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {
    Route::prefix('export')->group(function () {
        Route::get('campaign', [CampaignController::class, 'export'])->name('lead.campaign.export');
        Route::get('campaign-mn', [CampaignController::class, 'export_mn'])->name('lead.campaign_mn.export');
        Route::get('leads', [LeadController::class, 'export'])->name('lead.leads.export');
        Route::get('calls', [CallController::class, 'export'])->name('lead.calls.export');
        Route::get('cpa', [CallController::class, 'exportCpa'])->name('lead.calls.export_cpa');
        Route::get('rpc', [CallController::class, 'exportRpc'])->name('lead.calls.export_rpc');
        Route::get('qa', [CallController::class, 'exportQa'])->name('lead.calls.export_qa');
        Route::get('pageviews', [LeadPageViewController::class, 'export'])->name('lead.pageviews.export');
    });
    /*
    |--------------------------------------------------------------------------
    | Backend Web Routes (Views only)
    |--------------------------------------------------------------------------
    */
    Route::group(['middleware' => ['permission:roles']], function () {
        Route::get('users/roles', fn() => view('backend.users.role-update'))->name('role.edit');
    });

    Route::group(['middleware' => ['permission:users']], function () {
        Route::resource('users', UserController::class);
    });
});
