<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Space;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\SpaceResource;
use App\Http\Requests\Space\StoreSpaceRequest;
use App\Http\Requests\Space\UpdateSpaceRequest;

class SpaceController extends Controller
{
    public function index(Request $request)
    {
        $query = Space::query()
            ->with(['owner', 'features'])
            ->withAvg('reviews as avg_rating', 'rating')
            ->where('approval_status', 'approved');

        if ($request->filled('type')) {
            $query->where('space_type', $request->string('type'));
        }

        if ($request->filled('min_price')) {
            $query->where('price_per_hour', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price_per_hour', '<=', $request->max_price);
        }

        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        if ($request->filled('capacity')) {
            $query->where('capacity_people', '>=', (int) $request->capacity);
        }

        $spaces = $query->latest()->paginate(15);

        return SpaceResource::collection($spaces);
    }

    public function show(Request $request, Space $space)
    {
        $user = $request->user();

        $canViewUnapproved = $user
            && ($user->role === 'admin' || $user->id === $space->user_id);

        if ($space->approval_status !== 'approved' && !$canViewUnapproved) {
            abort(404);
        }

        $space->load(['owner', 'features'])
            ->loadAvg('reviews as avg_rating', 'rating');

        return new SpaceResource($space);
    }

    public function store(StoreSpaceRequest $request)
    {
        $this->authorize('create', Space::class);

        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['approval_status'] = 'pending'; // force default regardless of input

        $featureIds = $data['feature_ids'] ?? [];
        unset($data['feature_ids']);

        $space = Space::create($data);
        $space->features()->sync($featureIds);

        $space->load(['owner', 'features'])->loadAvg('reviews as avg_rating', 'rating');

        return response()->json([
            'message' => 'Space created successfully',
            'data' => new SpaceResource($space),
        ], 201);
    }

    public function update(UpdateSpaceRequest $request, Space $space)
    {
        $this->authorize('update', $space);

        $data = $request->validated();
        $featureIds = $data['feature_ids'] ?? null;
        unset($data['feature_ids']);

        // keep approval protected from user input
        unset($data['approval_status'], $data['user_id']);

        $space->update($data);

        if (is_array($featureIds)) {
            $space->features()->sync($featureIds);
        }

        $space->load(['owner', 'features'])->loadAvg('reviews as avg_rating', 'rating');

        return response()->json([
            'message' => 'Space updated successfully',
            'data' => new SpaceResource($space),
        ]);
    }

    public function destroy(Space $space)
    {
        $this->authorize('delete', $space);

        $space->delete();

        return response()->json([
            'message' => 'Space deleted successfully',
        ]);
    }

    public function mySpaces(Request $request)
    {
        abort_unless($request->user()->role === 'space_owner', 403);

        $spaces = Space::query()
            ->with(['owner', 'features'])
            ->withAvg('reviews as avg_rating', 'rating')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return SpaceResource::collection($spaces);
    }
}
