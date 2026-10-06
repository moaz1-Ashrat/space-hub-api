<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\FeatureResource;
use App\Models\Feature;

class FeatureController extends Controller
{
    /**
     * GET /api/v1/features
     * List all available features (public)
     */
    public function index()
    {
        $features = Feature::query()
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return FeatureResource::collection($features);
    }
}