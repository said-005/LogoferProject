<?php

namespace App\Http\Controllers;

use App\Models\Production;
use App\Http\Requests\StoreProductionRequest;
use App\Http\Requests\UpdateProductionRequest;
use App\Http\Resources\ProductionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

class ProductionController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|JsonResponse
     */
    public function index()
    {
        try {
            $productions = Production::orderBy('created_at', 'desc')->get();
            Log::info('Retrieved all productions', ['count' => $productions->count()]);
            return ProductionResource::collection($productions);
        } catch (Exception $e) {
            Log::error('Failed to retrieve productions: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve productions',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param StoreProductionRequest $request
     * @return JsonResponse
     */
    public function store(StoreProductionRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $production = Production::create($validated);
            
            Log::info('Production created successfully', ['code' => $production->production_code]);
            
            return response()->json([
                'message' => 'Production created successfully',
                'data' => new ProductionResource($production)
            ], Response::HTTP_CREATED);
            
        } catch (QueryException $e) {
            Log::error('Database error while creating production: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Failed to create production: database error',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
            
        } catch (Exception $e) {
            Log::error('Failed to create production: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Failed to create production',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     * 
     * @param string $code_production
     * @return JsonResponse
     */
    public function show(string $code_production): JsonResponse
    {
        try {
            $production = Production::where('production_code', $code_production)->firstOrFail();
            Log::info('Retrieved production', ['code' => $code_production]);
            
            return response()->json([
                'data' => new ProductionResource($production)
            ]);
            
        } catch (ModelNotFoundException $e) {
            Log::warning('Production not found', ['code' => $code_production]);
            
            return response()->json([
                'message' => 'Production not found'
            ], Response::HTTP_NOT_FOUND);
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve production: ' . $e->getMessage(), ['code' => $code_production]);
            
            return response()->json([
                'message' => 'Failed to retrieve production',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     * 
     * @param UpdateProductionRequest $request
     * @param string $code_production
     * @return JsonResponse
     */
    public function update(UpdateProductionRequest $request, string $code_production): JsonResponse
    {
        try {
            $production = Production::where('production_code', $code_production)->firstOrFail();
            $validated = $request->validated();
            
            $production->update($validated);
            
            Log::info('Production updated successfully', ['code' => $code_production]);
            
            return response()->json([
                'message' => 'Production updated successfully',
                'data' => new ProductionResource($production)
            ]);
            
        } catch (ModelNotFoundException $e) {
            Log::warning('Production not found for update', ['code' => $code_production]);
            
            return response()->json([
                'message' => 'Production not found'
            ], Response::HTTP_NOT_FOUND);
            
        } catch (QueryException $e) {
            Log::error('Database error while updating production: ' . $e->getMessage(), ['code' => $code_production]);
            
            return response()->json([
                'message' => 'Failed to update production: database error',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
            
        } catch (Exception $e) {
            Log::error('Failed to update production: ' . $e->getMessage(), ['code' => $code_production]);
            
            return response()->json([
                'message' => 'Failed to update production',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @param string $production_code
     * @return JsonResponse
     */
    public function destroy(string $production_code): JsonResponse
    {
        try {
            $production = Production::where('production_code', $production_code)->firstOrFail();
            
            $production->delete();
            
            Log::info('Production deleted successfully', ['code' => $production_code]);
            
            return response()->json([
                'message' => 'Production deleted successfully'
            ], Response::HTTP_OK);
            
        } catch (ModelNotFoundException $e) {
            Log::warning('Production not found for deletion', ['code' => $production_code]);
            
            return response()->json([
                'message' => 'Production not found'
            ], Response::HTTP_NOT_FOUND);
            
        } catch (QueryException $e) {
            Log::error('Database error while deleting production: ' . $e->getMessage(), ['code' => $production_code]);
            
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Cannot delete production: it is referenced by other records'
                ], Response::HTTP_CONFLICT);
            }
            
            return response()->json([
                'message' => 'Database error while deleting production',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
            
        } catch (Exception $e) {
            Log::error('Failed to delete production: ' . $e->getMessage(), ['code' => $production_code]);
            
            return response()->json([
                'message' => 'Failed to delete production',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}