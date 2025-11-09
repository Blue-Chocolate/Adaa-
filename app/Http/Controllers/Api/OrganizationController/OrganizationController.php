<?php

namespace App\Http\Controllers\Api\OrganizationController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Actions\Organization\{
    StoreOrganizationAction,
    UpdateOrganizationAction,
    DeleteOrganizationAction
};
use App\Repositories\OrganizationRepository;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Exception;
use Throwable;

class OrganizationController extends Controller
{
    protected $repo;

    public function __construct(OrganizationRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'limit' => 'sometimes|integer|min:1|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid request parameters',
                    'errors' => $validator->errors()
                ], 422);
            }

            $limit = $request->query('limit', 10);
            $orgs = $this->repo->allWithUser($limit);

            return response()->json([
                'success' => true,
                'message' => 'Organizations retrieved successfully',
                'data' => $orgs
            ], 200);

        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database error occurred',
                'error' => config('app.debug') ? $e->getMessage() : 'Unable to fetch organizations'
            ], 500);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch organizations',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred'
            ], 500);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Critical error occurred',
                'error' => config('app.debug') ? $e->getMessage() : 'System error'
            ], 500);
        }
    }

    public function store(Request $request, StoreOrganizationAction $action)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'sector' => 'required|string|max:255',
                'established_at' => 'required|date|before_or_equal:today',
                'email' => 'required|email|max:255|unique:organizations,email',
                'phone' => 'required|string|max:20',
                'address' => 'required|string|max:500',
                'license_number' => 'required|string|max:100|unique:organizations,license_number',
                'executive_name' => 'required|string|max:255',
                'user_id' => 'sometimes|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            
            // Auto-assign authenticated user if not provided
            if (!isset($data['user_id'])) {
                if (auth()->check()) {
                    $data['user_id'] = auth()->id();
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Authentication required',
                        'error' => 'User must be authenticated or user_id must be provided'
                    ], 401);
                }
            }

            $organization = $action->execute($data);

            return response()->json([
                'success' => true,
                'message' => 'Organization created successfully',
                'data' => $organization
            ], 201);

        } catch (QueryException $e) {
            // Handle database specific errors
            if ($e->getCode() === '23000') {
                return response()->json([
                    'success' => false,
                    'message' => 'Duplicate entry detected',
                    'error' => 'An organization with this email or license number already exists'
                ], 409);
            }

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred',
                'error' => config('app.debug') ? $e->getMessage() : 'Unable to create organization'
            ], 500);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error in action',
                'errors' => $e->errors()
            ], 422);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Creation failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Unable to create organization'
            ], 500);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Critical error during creation',
                'error' => config('app.debug') ? $e->getMessage() : 'System error'
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            // Validate ID format
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid organization ID',
                    'error' => 'ID must be a positive integer'
                ], 400);
            }

            $org = $this->repo->findById($id);

            return response()->json([
                'success' => true,
                'message' => 'Organization retrieved successfully',
                'data' => $org
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Organization not found',
                'error' => "No organization found with ID: {$id}"
            ], 404);

        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database error occurred',
                'error' => config('app.debug') ? $e->getMessage() : 'Unable to retrieve organization'
            ], 500);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve organization',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred'
            ], 500);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Critical error occurred',
                'error' => config('app.debug') ? $e->getMessage() : 'System error'
            ], 500);
        }
    }

    public function update(Request $request, $id, UpdateOrganizationAction $action)
    {
        try {
            // Validate ID format
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid organization ID',
                    'error' => 'ID must be a positive integer'
                ], 400);
            }

            // Check if at least one field is provided for update
            if (empty($request->all())) {
                return response()->json([
                    'success' => false,
                    'message' => 'No data provided for update',
                    'error' => 'At least one field must be provided'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'sector' => 'sometimes|string|max:255',
                'established_at' => 'sometimes|date|before_or_equal:today',
                'email' => 'sometimes|email|max:255|unique:organizations,email,' . $id,
                'phone' => 'sometimes|string|max:20',
                'address' => 'sometimes|string|max:500',
                'license_number' => 'sometimes|string|max:100|unique:organizations,license_number,' . $id,
                'executive_name' => 'sometimes|string|max:255',
                'user_id' => 'sometimes|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $organization = $action->execute($id, $validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Organization updated successfully',
                'data' => $organization
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Organization not found',
                'error' => "No organization found with ID: {$id}"
            ], 404);

        } catch (QueryException $e) {
            // Handle database specific errors
            if ($e->getCode() === '23000') {
                return response()->json([
                    'success' => false,
                    'message' => 'Duplicate entry detected',
                    'error' => 'An organization with this email or license number already exists'
                ], 409);
            }

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred',
                'error' => config('app.debug') ? $e->getMessage() : 'Unable to update organization'
            ], 500);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error in action',
                'errors' => $e->errors()
            ], 422);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Update failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Unable to update organization'
            ], 500);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Critical error during update',
                'error' => config('app.debug') ? $e->getMessage() : 'System error'
            ], 500);
        }
    }

    public function destroy($id, DeleteOrganizationAction $action)
    {
        try {
            // Validate ID format
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid organization ID',
                    'error' => 'ID must be a positive integer'
                ], 400);
            }

            $action->execute($id);

            return response()->json([
                'success' => true,
                'message' => 'Organization deleted successfully',
                'data' => null
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Organization not found',
                'error' => "No organization found with ID: {$id}"
            ], 404);

        } catch (QueryException $e) {
            // Handle foreign key constraint violations
            if ($e->getCode() === '23000') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete organization',
                    'error' => 'This organization has related records and cannot be deleted'
                ], 409);
            }

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred',
                'error' => config('app.debug') ? $e->getMessage() : 'Unable to delete organization'
            ], 500);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Deletion failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Unable to delete organization'
            ], 500);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Critical error during deletion',
                'error' => config('app.debug') ? $e->getMessage() : 'System error'
            ], 500);
        }
    }
}