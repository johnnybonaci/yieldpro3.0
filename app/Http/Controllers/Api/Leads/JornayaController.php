<?php

namespace App\Http\Controllers\Api\Leads;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\Leads\JornayaLeadRepository;

class JornayaController extends Controller
{
    public function __construct(
        protected JornayaLeadRepository $jornaya_lead_repository,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $date_start = $request->get('date_start', now()->format('Y-m-d'));
        $date_end = $request->get('date_end', now()->format('Y-m-d'));

        $page = $request->get('page', 1);
        $size = $request->get('size', 20);
        $leads = $this->jornaya_lead_repository->getUniversalLeadId($date_start, $date_end);
        $result = $leads->paginate($size, ['*'], 'page', $page);

        return $result;
    }
}
