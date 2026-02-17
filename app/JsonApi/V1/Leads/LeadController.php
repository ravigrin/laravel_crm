<?php

namespace App\JsonApi\V1\Leads;

use App\Enums\DefaultStatuses;
use App\Exceptions\CrmException;
use App\Jobs\Integrations\AutoIntegrationJob;
use App\Services\Lead\LeadResendService;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use LaravelJsonApi\Contracts\Routing\Route;
use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Core\Document\Error;
use LaravelJsonApi\Core\Facades\JsonApi;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Core\Responses\ErrorResponse;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use LaravelJsonApi\Laravel\Http\Requests\ResourceQuery;

class LeadController extends JsonApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Route $route, Store $store)
    {
        try {
            // Use parent method which handles pagination and filtering properly
            return parent::index($route, $store);
        } catch (\Exception $e) {
            Log::error('Error in index method', [
                'exception' => $e,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ErrorResponse::error(
                Error::fromArray([
                    'title' => 'Internal Server Error',
                    'detail' => 'An error occurred while processing the request',
                    'status' => '500',
                ])
            );
        }
    }

    /**
     * Store a newly created resource.
     */
    public function store(Route $route, Store $store)
    {
        try {
            // Use standard JSON:API store process - it will use LeadRequest for validation
            // and LeadAdapter for creating, which will trigger model events
            // Default values are set in Lead::booted() method
            $request = \LaravelJsonApi\Laravel\Http\Requests\ResourceRequest::forResource(
                $resourceType = $route->resourceType()
            );
            $query = ResourceQuery::queryOne($resourceType);
            $response = null;

            // Support hooks like parent::store
            if (method_exists($this, 'saving')) {
                $response = $this->saving(null, $request, $query);
            }

            if (!$response && method_exists($this, 'creating')) {
                $response = $this->creating($request, $query);
            }

            if ($response) {
                return $response;
            }

            // Create the resource through Store
            $model = $store
                ->create($resourceType)
                ->withRequest($query)
                ->store($request->validated());

            // Dispatch integration job if model is a Lead
            if ($model instanceof Lead && $model->id) {
                try {
                    $jobRequest = request();
                    $jobDelay = $jobRequest->input('meta.delay_sec', $jobRequest->input('meta.delaySec', 0));
                    AutoIntegrationJob::dispatch($model->id)->delay($jobDelay);
                } catch (\Exception $e) {
                    Log::warning('Could not dispatch auto integration job after lead creation', [
                        'exception' => $e,
                        'message' => $e->getMessage()
                    ]);
                }
            }

            // Support hooks like parent::store
            if (method_exists($this, 'created')) {
                $response = $this->created($model, $request, $query);
            }

            if (!$response && method_exists($this, 'saved')) {
                $response = $this->saved($model, $request, $query);
            }

            // Return DataResponse like parent::store does
            return $response ?? DataResponse::make($model)
                    ->withQueryParameters($query)
                    ->didCreate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Let validation exceptions bubble up so they return 422
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error in store method', [
                'exception' => $e,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ErrorResponse::error(
                Error::fromArray([
                    'title' => 'Failed to create lead',
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
        try {
            // Get resource ID before calling parent (in case parent throws exception)
            $resourceId = $route->resourceId();
            
            // Use parent method which properly handles 404 with correct Content-Type
            $response = parent::show($route, $store);
            
            // Mark lead as viewed if it wasn't already viewed
            // If lead wasn't found, parent::show will throw ModelNotFoundException
            try {
                $lead = Lead::find($resourceId);
                if ($lead && !$lead->viewed) {
                    $lead->forceFill(['viewed' => true])->save();
                }
            } catch (\Exception $e) {
                // Ignore errors when marking as viewed
                Log::debug('Could not mark lead as viewed', [
                    'lead_id' => $resourceId,
                    'exception' => $e->getMessage()
                ]);
            }
            
            return $response;
        } catch (\Exception $e) {
            Log::error('Error in show method', [
                'exception' => $e,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Re-throw ModelNotFoundException to let Laravel JSON:API handle it
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                throw $e;
            }
            return ErrorResponse::error(
                Error::fromArray([
                    'title' => 'Internal Server Error',
                    'detail' => 'An error occurred while processing the request',
                    'status' => '500',
                ])
            );
        }
    }

    /**
     * Update the specified resource.
     */
    public function update(Route $route, Store $store)
    {
        try {
            // Use parent to handle update
            return parent::update($route, $store);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Let validation exceptions bubble up so they return 422
            throw $e;
        } catch (\LaravelJsonApi\Core\Exceptions\JsonApiException $e) {
            // Let JSON:API exceptions (including validation errors) bubble up
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error in update method', [
                'exception' => $e,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ErrorResponse::error(
                Error::fromArray([
                    'title' => 'Failed to update lead',
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
            // Get the resource ID and find the lead
            $resourceId = $route->resourceId();
            $lead = Lead::findOrFail($resourceId);
            
            // Call delete() which will respect SoftDeletes trait
            // This will perform soft deletion since Lead model uses SoftDeletes
            $lead->delete();
            
            // Return 204 No Content as per JSON:API spec
            return response(null, HttpResponse::HTTP_NO_CONTENT);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Re-throw ModelNotFoundException to let Laravel JSON:API handle it (returns 404)
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error in destroy method', [
                'exception' => $e,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ErrorResponse::error(
                Error::fromArray([
                    'title' => 'Failed to delete lead',
                    'detail' => 'Can\'t remove items',
                    'status' => '500',
                ])
            );
        }
    }

    /**
     * Bulk update status for multiple leads.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdateStatus(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'leadIds' => 'required|array|min:1|max:1000',
                'leadIds.*' => 'required|integer|exists:leads,id',
                'status' => 'required|integer|min:1',
            ], [
                'leadIds.required' => 'Lead IDs are required',
                'leadIds.array' => 'Lead IDs must be an array',
                'leadIds.min' => 'At least one lead ID is required',
                'leadIds.max' => 'Maximum 1000 leads can be updated at once',
                'leadIds.*.required' => 'Each lead ID is required',
                'leadIds.*.integer' => 'Each lead ID must be an integer',
                'leadIds.*.exists' => 'One or more lead IDs do not exist',
                'status.required' => 'Status is required',
                'status.integer' => 'Status must be an integer',
                'status.min' => 'Status must be a positive integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors()
                ], 422);
            }

            $leadIds = $request->input('leadIds');
            $status = $request->input('status');

            // Update leads in a transaction
            DB::beginTransaction();
            try {
                $updatedCount = Lead::whereIn('id', $leadIds)
                    ->update(['status' => $status]);

                DB::commit();

                Log::info('Bulk status update completed', [
                    'lead_ids' => $leadIds,
                    'status' => $status,
                    'updated_count' => $updatedCount,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Successfully updated status for {$updatedCount} lead(s)",
                    'data' => [
                        'updated_count' => $updatedCount,
                        'status' => $status,
                        'lead_ids' => $leadIds,
                    ]
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error in bulkUpdateStatus method', [
                'exception' => $e,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update leads status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get leads grouped by status for kanban view.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getKanban(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'user_id' => 'sometimes|integer|exists:users,id',
                'project_id' => 'sometimes|integer|exists:projects,id',
                'quiz_id' => 'sometimes|integer|exists:quizzes,id',
                'include_statuses' => 'sometimes|array',
                'include_statuses.*' => 'integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = Lead::query();

            // Apply filters
            if ($request->has('user_id')) {
                $query->where('user_id', $request->input('user_id'));
            }

            if ($request->has('project_id')) {
                $query->where('project_id', $request->input('project_id'));
            }

            if ($request->has('quiz_id')) {
                $query->where('quiz_id', $request->input('quiz_id'));
            }

            // Get all leads with their statuses
            $leads = $query->select('id', 'name', 'email', 'phone', 'status', 'created_at', 'updated_at', 'viewed', 'paid', 'blocked')
                ->orderBy('created_at', 'desc')
                ->get();

            // Get unique status values from leads
            $statusValues = $leads->pluck('status')->unique()->sort()->values()->toArray();

            // Get custom statuses metadata if project_id is provided
            $customStatusesMap = [];
            if ($request->has('project_id')) {
                $customStatuses = \App\Models\Status::where('project_id', $request->input('project_id'))
                    ->orderBy('order', 'asc')
                    ->get();
                
                // Create a map by code for quick lookup
                foreach ($customStatuses as $status) {
                    $customStatusesMap[$status->code] = [
                        'id' => $status->id,
                        'code' => $status->code,
                        'name' => $status->name,
                        'order' => $status->order,
                        'color' => $status->color,
                        'is_default' => $status->is_default,
                    ];
                }
            }

            // Map status values to status metadata
            $statusMap = [
                DefaultStatuses::New->value => [
                    'id' => DefaultStatuses::New->value,
                    'code' => 'new',
                    'name' => __('statuses.new'),
                    'order' => 1,
                    'color' => '#3b82f6',
                    'is_default' => true,
                ],
                DefaultStatuses::AtWork->value => [
                    'id' => DefaultStatuses::AtWork->value,
                    'code' => 'at_work',
                    'name' => __('statuses.at_work'),
                    'order' => 2,
                    'color' => '#f59e0b',
                    'is_default' => true,
                ],
                DefaultStatuses::Success->value => [
                    'id' => DefaultStatuses::Success->value,
                    'code' => 'success',
                    'name' => __('statuses.success'),
                    'order' => 3,
                    'color' => '#10b981',
                    'is_default' => true,
                ],
                DefaultStatuses::Declined->value => [
                    'id' => DefaultStatuses::Declined->value,
                    'code' => 'declined',
                    'name' => __('statuses.declined'),
                    'order' => 4,
                    'color' => '#ef4444',
                    'is_default' => true,
                ],
            ];

            // Override with custom statuses if they match by code
            foreach ($customStatusesMap as $code => $customStatus) {
                // Try to find matching default status by code
                foreach ($statusMap as $statusId => $statusData) {
                    if ($statusData['code'] === $code) {
                        $statusMap[$statusId] = array_merge($statusData, $customStatus, ['id' => $statusId]);
                        break;
                    }
                }
            }

            // Build statuses array for kanban
            $statuses = [];
            foreach ($statusValues as $statusValue) {
                if (isset($statusMap[$statusValue])) {
                    $statuses[] = $statusMap[$statusValue];
                } else {
                    // Unknown status value
                    $statuses[] = [
                        'id' => $statusValue,
                        'code' => 'unknown',
                        'name' => "Статус {$statusValue}",
                        'order' => 999,
                        'color' => '#6b7280',
                        'is_default' => false,
                    ];
                }
            }

            // Group leads by status
            $kanbanData = [];
            foreach ($statuses as $status) {
                $statusId = $status['id'];
                $statusLeads = $leads->where('status', $statusId)->values()->map(function ($lead) {
                    return [
                        'id' => $lead->id,
                        'name' => $lead->name,
                        'email' => $lead->email,
                        'phone' => $lead->phone,
                        'status' => $lead->status,
                        'viewed' => $lead->viewed,
                        'paid' => $lead->paid,
                        'blocked' => $lead->blocked,
                        'created_at' => $lead->created_at?->toIso8601String(),
                        'updated_at' => $lead->updated_at?->toIso8601String(),
                    ];
                });

                $kanbanData[] = [
                    'status' => $status,
                    'leads' => $statusLeads,
                    'count' => $statusLeads->count(),
                ];
            }

            // Sort by status order
            usort($kanbanData, function ($a, $b) {
                return $a['status']['order'] <=> $b['status']['order'];
            });

            return response()->json([
                'success' => true,
                'data' => $kanbanData,
                'meta' => [
                    'total_leads' => $leads->count(),
                    'total_statuses' => count($kanbanData),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error in getKanban method', [
                'exception' => $e,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get kanban data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resend a single lead to integrations.
     * 
     * @param Request $request
     * @param int $leadId
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendLead(Request $request, int $leadId)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'integration_types' => 'sometimes|array',
                'integration_types.*' => 'string|in:email,amocrm,telegram,bitrix24,webhooks,retailcrm,getresponse,sendpulse,unisender,lptracker,uontravel',
                'credentials' => 'sometimes|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $integrationTypes = $request->input('integration_types', []);
            $credentials = $request->input('credentials', []);

            // Use LeadResendService for centralized logic
            $resendService = app(LeadResendService::class);
            $result = $resendService->resendLead($leadId, $integrationTypes, $credentials);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'lead_id' => $result['lead_id'],
                    'method' => $result['method'] ?? null,
                    'integration_types' => $result['integration_types'] ?? null,
                    'integrations_count' => $result['integrations_count'] ?? null,
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found'
            ], 404);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error in resendLead method', [
                'exception' => $e,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to resend lead',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk resend multiple leads to integrations.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkResendLeads(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'lead_ids' => 'required|array|min:1|max:1000',
                'lead_ids.*' => 'required|integer|exists:leads,id',
                'integration_types' => 'sometimes|array',
                'integration_types.*' => 'string|in:email,amocrm,telegram,bitrix24,webhooks,retailcrm,getresponse,sendpulse,unisender,lptracker,uontravel',
                'credentials' => 'sometimes|array',
            ], [
                'lead_ids.required' => 'Lead IDs are required',
                'lead_ids.array' => 'Lead IDs must be an array',
                'lead_ids.min' => 'At least one lead ID is required',
                'lead_ids.max' => 'Maximum 1000 leads can be resent at once',
                'lead_ids.*.required' => 'Each lead ID is required',
                'lead_ids.*.integer' => 'Each lead ID must be an integer',
                'lead_ids.*.exists' => 'One or more lead IDs do not exist',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $leadIds = $request->input('lead_ids');
            $integrationTypes = $request->input('integration_types', []);
            $credentials = $request->input('credentials', []);

            // Use LeadResendService for centralized logic
            $resendService = app(LeadResendService::class);
            $result = $resendService->bulkResendLeads($leadIds, $integrationTypes, $credentials);

            return response()->json([
                'success' => true,
                'message' => "Successfully queued resend for {$result['dispatched_count']} lead(s)",
                'data' => [
                    'dispatched_count' => $result['dispatched_count'],
                    'total_count' => $result['total_count'],
                    'errors_count' => $result['errors_count'],
                    'integration_types' => $integrationTypes,
                    'errors' => $result['errors']
                ]
            ], 200);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error in bulkResendLeads method', [
                'exception' => $e,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk resend leads',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get filter counts for dropdowns (cities, quizzes, statuses, etc.).
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFilterCounts(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'user_id' => 'sometimes|integer|exists:users,id',
                'project_id' => 'sometimes|integer|exists:projects,id',
                'quiz_id' => 'sometimes|integer|exists:quizzes,id',
                'filters' => 'sometimes|array',
                'filters.*' => 'string|in:city,quiz_id,status,paid,blocked',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $baseQuery = Lead::query();

            // Apply base filters
            if ($request->has('user_id')) {
                $baseQuery->where('user_id', $request->input('user_id'));
            }

            if ($request->has('project_id')) {
                $baseQuery->where('project_id', $request->input('project_id'));
            }

            if ($request->has('quiz_id')) {
                $baseQuery->where('quiz_id', $request->input('quiz_id'));
            }

            $requestedFilters = $request->input('filters', ['city', 'quiz_id', 'status', 'paid', 'blocked']);
            $counts = [];

            // City counts
            if (in_array('city', $requestedFilters)) {
                $cityCounts = (clone $baseQuery)
                    ->select('city', DB::raw('count(*) as count'))
                    ->whereNotNull('city')
                    ->groupBy('city')
                    ->orderBy('count', 'desc')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [$item->city => $item->count];
                    })
                    ->toArray();

                $counts['cities'] = $cityCounts;
            }

            // Quiz counts
            if (in_array('quiz_id', $requestedFilters)) {
                $quizCounts = (clone $baseQuery)
                    ->select('quiz_id', DB::raw('count(*) as count'))
                    ->whereNotNull('quiz_id')
                    ->groupBy('quiz_id')
                    ->orderBy('count', 'desc')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [(string)$item->quiz_id => $item->count];
                    })
                    ->toArray();

                $counts['quizzes'] = $quizCounts;
            }

            // Status counts
            if (in_array('status', $requestedFilters)) {
                $statusCounts = (clone $baseQuery)
                    ->select('status', DB::raw('count(*) as count'))
                    ->whereNotNull('status')
                    ->groupBy('status')
                    ->orderBy('status', 'asc')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [(string)$item->status => $item->count];
                    })
                    ->toArray();

                $counts['statuses'] = $statusCounts;
            }

            // Paid counts
            if (in_array('paid', $requestedFilters)) {
                $paidCount = (clone $baseQuery)->where('paid', true)->count();
                $unpaidCount = (clone $baseQuery)->where('paid', false)->count();

                $counts['paid'] = [
                    'paid' => $paidCount,
                    'unpaid' => $unpaidCount,
                ];
            }

            // Blocked counts
            if (in_array('blocked', $requestedFilters)) {
                $blockedCount = (clone $baseQuery)->where('blocked', true)->count();
                $unblockedCount = (clone $baseQuery)->where('blocked', false)->count();

                $counts['blocked'] = [
                    'blocked' => $blockedCount,
                    'unblocked' => $unblockedCount,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $counts
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error in getFilterCounts method', [
                'exception' => $e,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get filter counts',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
