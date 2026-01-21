<?php

declare(strict_types=1);

namespace Core\Mod\Api\Controllers;

use Core\Front\Controller;
use Core\Mod\Tenant\Models\Package;
use Illuminate\Http\JsonResponse;

class ProductApiController extends Controller
{
    /**
     * List all available products (packages) for Blesta.
     */
    public function index(): JsonResponse
    {
        $packages = Package::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Package $package) => [
                'code' => $package->code,
                'name' => $package->name,
                'description' => $package->description,
                'is_base_package' => $package->is_base_package,
                'is_stackable' => $package->is_stackable,
                'feature_count' => $package->features()->count(),
            ]);

        return response()->json([
            'success' => true,
            'products' => $packages,
        ]);
    }

    /**
     * Get a single product by code.
     */
    public function show(string $code): JsonResponse
    {
        $package = Package::where('code', $code)
            ->with('features')
            ->first();

        if (! $package) {
            return response()->json([
                'success' => false,
                'error' => "Product '{$code}' not found",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'product' => [
                'code' => $package->code,
                'name' => $package->name,
                'description' => $package->description,
                'is_base_package' => $package->is_base_package,
                'is_stackable' => $package->is_stackable,
                'is_active' => $package->is_active,
                'features' => $package->features->map(fn ($feature) => [
                    'code' => $feature->code,
                    'name' => $feature->name,
                    'type' => $feature->type,
                    'limit_value' => $feature->pivot->limit_value,
                ]),
            ],
        ]);
    }

    /**
     * Connection test endpoint.
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Host Hub API is operational',
            'version' => '1.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
