<?php

namespace App\Http\Controllers\Api\Leads;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Leads\IpQualityService;
use App\Repositories\Leads\JornayaLeadRepository;

class JornayaController extends Controller
{
    public function __construct(
        protected JornayaLeadRepository $jornaya_lead_repository,
        protected IpQualityService $ip_quality_service,
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

    /**
     * Validate ip Address using Jornaya API.
     */
    public function iqQuality(Request $request): JsonResponse
    {
        if (filter_var($request->get('ip'), FILTER_VALIDATE_IP) !== false) {
            return response()->json($this->ip_quality_service->index($request->get('ip')));
        }

        return response()->json([
            'status' => 'error',
            'message' => 'The provided IP address is invalid.',
        ], 422);
    }
}
