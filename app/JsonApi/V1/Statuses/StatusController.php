<?php

namespace App\JsonApi\V1\Statuses;

use App\Enums\DefaultStatuses;
use App\Exceptions\CrmException;
use App\Models\Lead;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use LaravelJsonApi\Contracts\Routing\Route;
use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Core\Document\Error;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Core\Responses\ErrorResponse;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use LaravelJsonApi\Laravel\Http\Requests\ResourceQuery;

class StatusController extends JsonApiController
{
    /**
     * Store a newly created resource.
     */
    public function store(Route $route, Store $store)
    {
        try {
            // Let the parent handle creation
            return parent::store($route, $store);
        } catch (CrmException $exception) {
            Log::critical($exception->getMessage());
            return ErrorResponse::error(
                Error::fromArray([
                    'title' => 'Failed to create status',
                    'detail' => 'Something went wrong',
                    'status' => '500',
                ])
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Route $route, Store $store)
    {
        $request = request();
        $resourceId = $route->resourceId();
        $status = Status::findOrFail($resourceId);
        
        $statusId = $request->query('filter.status_id');
        $externalEntityId = $request->query('filter.external_entity_id');
        
        if (($statusId && $status->status_id != $statusId) || 
            ($externalEntityId && $status->external_entity_id !== $externalEntityId)) {
            return ErrorResponse::error(
                Error::fromArray([
                    'title' => 'Status not found',
                    'detail' => 'Status not found',
                    'status' => '404',
                ])
            );
        }

        return DataResponse::make($status)->withQueryParameters(
            ResourceQuery::queryOne($route->resourceType())
        );
    }

    /**
     * Update the specified resource.
     */
    public function update(Route $route, Store $store)
    {
        try {
            $request = request();
            $resourceId = $route->resourceId();
            $status = Status::findOrFail($resourceId);
            
            // Get validation data from request
            $statusId = $request->input('data.attributes.statusId');
            $externalEntityId = $request->input('data.attributes.externalEntityId');
            
            if (($statusId && $status->status_id != $statusId) || 
                ($externalEntityId && $status->external_entity_id !== $externalEntityId)) {
                return ErrorResponse::error(
                    Error::fromArray([
                        'title' => 'Status not found',
                        'detail' => 'Status not found',
                        'status' => '404',
                    ])
                );
            }

            // Let the parent handle update
            return parent::update($route, $store);
        } catch (CrmException $e) {
            Log::critical($e->getMessage());
            return ErrorResponse::error(
                Error::fromArray([
                    'title' => 'Failed to update status',
                    'detail' => 'error while updating data',
                    'status' => '500',
                ])
            );
        }
    }

    /**
     * Remove the specified resource.
     */
    public function destroy(Route $route, Store $store)
    {
        try {
            $request = request();
            $resourceId = $route->resourceId();
            $status = Status::findOrFail($resourceId);
            
            $statusId = $request->query('filter.status_id');
            $externalEntityId = $request->query('filter.external_entity_id');
            
            if (($statusId && $status->status_id != $statusId) || 
                ($externalEntityId && $status->external_entity_id !== $externalEntityId)) {
                return ErrorResponse::error(
                    Error::fromArray([
                        'title' => 'Status not found',
                        'detail' => 'Status not found',
                        'status' => '404',
                    ])
                );
            }

            $status->delete();

            return response(null, HttpResponse::HTTP_NO_CONTENT);
        } catch (CrmException $e) {
            Log::critical($e->getMessage(), $e->getTrace());
            return ErrorResponse::error(
                Error::fromArray([
                    'title' => 'Failed to delete status',
                    'detail' => 'error while deleting data',
                    'status' => '500',
                ])
            );
        }
    }

    /**
     * Display a listing of the resource with defaults.
     */
    public function index(Route $route, Store $store)
    {
        $request = request();
        $externalEntityId = $request->query('filter.external_entity_id');
        
        // For JSON:API, we need to return the actual Status models
        // So let's query all statuses for the external_entity_id
        $query = Status::query();
        if ($externalEntityId) {
            $query->where('external_entity_id', $externalEntityId);
        }
        $query->orderBy('status_id', 'ASC');

        $resourceQuery = ResourceQuery::queryMany($route->resourceType());
        $data = $store
            ->queryAll($route->resourceType())
            ->withRequest($resourceQuery)
            ->firstOrPaginate($resourceQuery->page());

        return DataResponse::make($data)->withQueryParameters($resourceQuery);
    }

    /**
     * Get statuses with lead counts for dropdowns and filters.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatusesWithCounts(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'user_id' => 'sometimes|integer|exists:users,id',
                'project_id' => 'sometimes|integer|exists:projects,id',
                'quiz_id' => 'sometimes|integer|exists:quizzes,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Build query for leads to count
            $leadsQuery = Lead::query();
            
            if ($request->has('user_id')) {
                $leadsQuery->where('user_id', $request->input('user_id'));
            }

            if ($request->has('project_id')) {
                $leadsQuery->where('project_id', $request->input('project_id'));
            }

            if ($request->has('quiz_id')) {
                $leadsQuery->where('quiz_id', $request->input('quiz_id'));
            }

            // Get lead counts by status
            $leadCounts = $leadsQuery
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            // Get custom statuses
            $statusesQuery = Status::query();
            
            if ($request->has('project_id')) {
                $statusesQuery->where('project_id', $request->input('project_id'));
            } elseif ($request->has('user_id')) {
                $statusesQuery->where('user_id', $request->input('user_id'));
            }

            $customStatuses = $statusesQuery
                ->orderBy('order', 'asc')
                ->get()
                ->map(function ($status) use ($leadCounts) {
                    // Map status code to default status value for counting
                    $statusValueMap = [
                        'new' => DefaultStatuses::New->value,
                        'at_work' => DefaultStatuses::AtWork->value,
                        'in-progress' => DefaultStatuses::AtWork->value,
                        'success' => DefaultStatuses::Success->value,
                        'declined' => DefaultStatuses::Declined->value,
                        'cancelled' => DefaultStatuses::Declined->value,
                    ];
                    
                    $statusValue = $statusValueMap[$status->code] ?? null;
                    $count = $statusValue ? ($leadCounts[$statusValue] ?? 0) : 0;
                    
                    return [
                        'id' => $status->id,
                        'code' => $status->code,
                        'name' => $status->name,
                        'order' => $status->order,
                        'color' => $status->color,
                        'is_default' => $status->is_default,
                        'count' => $count,
                        'status_value' => $statusValue, // The numeric value used in leads.status
                    ];
                })
                ->toArray();

            // Add default statuses with counts
            $defaultStatuses = [
                [
                    'id' => DefaultStatuses::New->value,
                    'code' => 'new',
                    'name' => __('statuses.new'),
                    'order' => 1,
                    'color' => '#3b82f6',
                    'is_default' => true,
                    'count' => $leadCounts[DefaultStatuses::New->value] ?? 0,
                ],
                [
                    'id' => DefaultStatuses::AtWork->value,
                    'code' => 'at_work',
                    'name' => __('statuses.at_work'),
                    'order' => 2,
                    'color' => '#f59e0b',
                    'is_default' => true,
                    'count' => $leadCounts[DefaultStatuses::AtWork->value] ?? 0,
                ],
                [
                    'id' => DefaultStatuses::Success->value,
                    'code' => 'success',
                    'name' => __('statuses.success'),
                    'order' => 3,
                    'color' => '#10b981',
                    'is_default' => true,
                    'count' => $leadCounts[DefaultStatuses::Success->value] ?? 0,
                ],
                [
                    'id' => DefaultStatuses::Declined->value,
                    'code' => 'declined',
                    'name' => __('statuses.declined'),
                    'order' => 4,
                    'color' => '#ef4444',
                    'is_default' => true,
                    'count' => $leadCounts[DefaultStatuses::Declined->value] ?? 0,
                ],
            ];

            // Merge custom and default statuses, avoiding duplicates
            $allStatuses = [];
            $usedIds = [];

            // Add default statuses first
            foreach ($defaultStatuses as $defaultStatus) {
                $allStatuses[] = $defaultStatus;
                $usedIds[] = $defaultStatus['id'];
            }

            // Add custom statuses that don't conflict with defaults
            foreach ($customStatuses as $customStatus) {
                if (!in_array($customStatus['id'], $usedIds)) {
                    $allStatuses[] = $customStatus;
                    $usedIds[] = $customStatus['id'];
                }
            }

            // Sort by order
            usort($allStatuses, function ($a, $b) {
                return $a['order'] <=> $b['order'];
            });

            // Calculate total count
            $totalCount = array_sum(array_column($allStatuses, 'count'));

            return response()->json([
                'success' => true,
                'data' => $allStatuses,
                'meta' => [
                    'total_statuses' => count($allStatuses),
                    'total_leads' => $totalCount,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error in getStatusesWithCounts method', [
                'exception' => $e,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get statuses with counts',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
