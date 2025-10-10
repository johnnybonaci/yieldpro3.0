<?php

namespace App\Http\Controllers\Api\Leads;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Leads\MediaAlphaResponse;

class MediaAlphaResponseController extends Controller
{
    /**
     * Obtener todas las respuestas con filtros para React.
     */
    public function index(Request $request): JsonResponse
    {
        $query = MediaAlphaResponse::with('config:placement_id,name');

        // Filtro por estado
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        // Filtro por placement_id
        if ($request->filled('placement_id')) {
            $query->byPlacementId($request->placement_id);
        }

        // Filtro por rango de fechas
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($request->date_from));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
        }

        // Filtro por teléfono
        if ($request->filled('phone')) {
            $query->where('phone', 'like', '%' . $request->phone . '%');
        }

        // Filtro por buyer ganador
        if ($request->filled('winning_buyer')) {
            $query->where('winning_buyer', 'like', '%' . $request->winning_buyer . '%');
        }

        // Filtro por rango de revenue
        if ($request->filled('min_revenue')) {
            $query->where('post_revenue', '>=', $request->min_revenue);
        }

        if ($request->filled('max_revenue')) {
            $query->where('post_revenue', '<=', $request->max_revenue);
        }

        // Filtro por estado de ping
        if ($request->filled('ping_status')) {
            $query->where('ping_status', $request->ping_status);
        }

        // Filtro por estado de post
        if ($request->filled('post_status')) {
            $query->where('post_status', $request->post_status);
        }

        // Solo con revenue
        if ($request->boolean('with_revenue')) {
            $query->withRevenue();
        }

        // Solo exitosos
        if ($request->boolean('successful_only')) {
            $query->successful();
        }

        // Solo fallidos
        if ($request->boolean('failed_only')) {
            $query->failed();
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        $allowedSorts = [
            'created_at',
            'phone',
            'placement_id',
            'ping_time',
            'post_time',
            'post_revenue',
            'total_buyers',
            'accepted_buyers',
            'highest_bid',
            'status',
            'winning_buyer',
        ];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        // Paginación
        $perPage = min($request->get('per_page', 15), 100); // Máximo 100 por página
        $responses = $query->paginate($perPage);

        // Agregar datos calculados a cada respuesta
        /** @var \Illuminate\Pagination\LengthAwarePaginator $responses */
        $responses->setCollection(
            $responses->getCollection()->transform(function ($response) {
                return [
                    'phone' => $response->phone,
                    'formatted_phone' => $response->formatted_phone,
                    'placement_id' => $response->placement_id,
                    'config_name' => $response->config->name ?? 'Sin configuración',
                    'leadid_id' => $response->leadid_id,

                    // Datos de ping
                    'ping_id' => $response->ping_id,
                    'ping_status' => $response->ping_status,
                    'ping_time' => $response->ping_time,
                    'ping_sent_at' => $response->ping_sent_at?->format('Y-m-d H:i:s'),
                    'ping_error' => $response->ping_error,

                    // Datos de post
                    'post_status' => $response->post_status,
                    'post_revenue' => $response->post_revenue,
                    'post_time' => $response->post_time,
                    'post_sent_at' => $response->post_sent_at?->format('Y-m-d H:i:s'),
                    'post_error' => $response->post_error,

                    // Estadísticas
                    'total_buyers' => $response->total_buyers,
                    'accepted_buyers' => $response->accepted_buyers,
                    'rejected_buyers' => $response->rejected_buyers,
                    'highest_bid' => $response->highest_bid,
                    'winning_buyer' => $response->winning_buyer,
                    'conversion_rate' => $response->conversion_rate,
                    'total_processing_time' => $response->total_processing_time,

                    // Estado general
                    'status' => $response->status,
                    'created_at' => $response->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $response->updated_at->format('Y-m-d H:i:s'),
                ];
            })
        );

        // Agregar estadísticas generales
        $stats = $this->getStatistics($request);

        return response()->json([
            'success' => true,
            'data' => $responses->items(),
            'pagination' => [
                'current_page' => $responses->currentPage(),
                'last_page' => $responses->lastPage(),
                'per_page' => $responses->perPage(),
                'total' => $responses->total(),
                'from' => $responses->firstItem(),
                'to' => $responses->lastItem(),
            ],
            'statistics' => $stats,
            'filters_applied' => $request->except(['page', 'per_page']),
        ]);
    }

    /**
     * Obtener estadísticas generales.
     */
    public function statistics(Request $request): JsonResponse
    {
        $stats = $this->getStatistics($request);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Obtener detalles de una respuesta específica.
     */
    public function show(string $phone): JsonResponse
    {
        $response = MediaAlphaResponse::with('config')->find($phone);

        if (!$response) {
            return response()->json([
                'success' => false,
                'message' => 'Respuesta no encontrada',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'phone' => $response->phone,
                'formatted_phone' => $response->formatted_phone,
                'placement_id' => $response->placement_id,
                'config_name' => $response->config->name ?? 'Sin configuración',
                'leadid_id' => $response->leadid_id,

                // Datos completos de ping
                'ping' => [
                    'id' => $response->ping_id,
                    'status' => $response->ping_status,
                    'time' => $response->ping_time,
                    'sent_at' => $response->ping_sent_at?->format('Y-m-d H:i:s'),
                    'error' => $response->ping_error,
                    'buyers' => $response->ping_buyers,
                    'raw_response' => $response->ping_raw_response,
                ],

                // Datos completos de post
                'post' => [
                    'status' => $response->post_status,
                    'revenue' => $response->post_revenue,
                    'time' => $response->post_time,
                    'sent_at' => $response->post_sent_at?->format('Y-m-d H:i:s'),
                    'error' => $response->post_error,
                    'buyers' => $response->post_buyers,
                    'raw_response' => $response->post_raw_response,
                ],

                // Estadísticas calculadas
                'statistics' => [
                    'total_buyers' => $response->total_buyers,
                    'accepted_buyers' => $response->accepted_buyers,
                    'rejected_buyers' => $response->rejected_buyers,
                    'highest_bid' => $response->highest_bid,
                    'winning_buyer' => $response->winning_buyer,
                    'conversion_rate' => $response->conversion_rate,
                    'total_processing_time' => $response->total_processing_time,
                ],

                'status' => $response->status,
                'created_at' => $response->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $response->updated_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Calcular estadísticas con filtros aplicados.
     */
    private function getStatistics(Request $request): array
    {
        $query = MediaAlphaResponse::query();

        // Aplicar los mismos filtros que en index
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('placement_id')) {
            $query->byPlacementId($request->placement_id);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($request->date_from));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
        }

        $total = $query->count();
        $successful = (clone $query)->successful()->count();
        $failed = (clone $query)->failed()->count();
        $processing = (clone $query)->byStatus('processing')->count();

        $totalRevenue = (clone $query)->sum('post_revenue') ?? 0;
        $avgRevenue = $total > 0 ? ((clone $query)->avg('post_revenue') ?? 0) : 0;
        $maxRevenue = (clone $query)->max('post_revenue') ?? 0;

        $avgPingTime = $total > 0 ? ((clone $query)->avg('ping_time') ?? 0) : 0;
        $avgPostTime = $total > 0 ? ((clone $query)->avg('post_time') ?? 0) : 0;

        $totalBuyers = (clone $query)->sum('total_buyers') ?? 0;
        $totalAccepted = (clone $query)->sum('accepted_buyers') ?? 0;
        $totalRejected = (clone $query)->sum('rejected_buyers') ?? 0;

        return [
            'total_responses' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'processing' => $processing,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,

            'revenue' => [
                'total' => round($totalRevenue, 2),
                'average' => round($avgRevenue, 2),
                'maximum' => round($maxRevenue, 2),
            ],

            'performance' => [
                'avg_ping_time' => round($avgPingTime, 3),
                'avg_post_time' => round($avgPostTime, 3),
                'avg_total_time' => round($avgPingTime + $avgPostTime, 3),
            ],

            'buyers' => [
                'total_contacted' => $totalBuyers,
                'total_accepted' => $totalAccepted,
                'total_rejected' => $totalRejected,
                'acceptance_rate' => $totalBuyers > 0 ? round(($totalAccepted / $totalBuyers) * 100, 2) : 0,
            ],
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function responses(Request $request)
    {
        $date_start = $request->get('date_start', now()->format('Y-m-d'));
        $date_end = $request->get('date_end', now()->format('Y-m-d'));

        $page = $request->get('page', 1);
        $size = $request->get('size', 20);

        return MediaAlphaResponse::whereBetween('date_history', [$date_start, $date_end])->filterFields()->sortsFields('created_at')->paginate($size, ['*'], 'page', $page);
    }
}
