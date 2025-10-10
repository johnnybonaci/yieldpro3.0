<?php

namespace App\Http\Controllers\Backend\Leads;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Support\Renderable;

class DashboardListController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function home(): Renderable
    {
        return view('dashboard');
    }
}
