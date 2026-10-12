<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Space;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminSpaceController extends Controller
{
    /**
     * List all spaces with filters (Admin).
     */
    public function index(Request $request)
    {
        $query = Space::query()->with(['owner:id,first_name,last_name,email']);

        // Filters
        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->string('approval_status'));
        }

        if ($request->filled('space_type')) {
            $query->where('space_type', $request->string('space_type'));
        }

        if ($request->filled('owner_id')) {
            $query->where('user_id', (int) $request->input('owner_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $spaces = $query->orderByDesc('created_at')->paginate(15);

        $items = $spaces->getCollection()->map(function ($space) {
            return [
                'id' => $space->id,
                'name' => $space->name,
                'location' => $space->location,
                'space_type' => $space->space_type,
                'price_per_hour' => (float) $space->price_per_hour,
                'capacity_people' => (int) $space->capacity_people,
                'approval_status' => $space->approval_status,
                'owner' => $space->owner ? [
                    'id' => $space->owner->id,
                    'name' => trim($space->owner->first_name . ' ' . $space->owner->last_name),
                    'email' => $space->owner->email,
                ] : null,
                'primary_image' => $space->primaryImage ? [
                    'id' => $space->primaryImage->id,
                    'url' => $space->primaryImage->url,
                ] : null,
                'created_at' => $space->created_at,
            ];
        });

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $spaces->currentPage(),
                'per_page' => $spaces->perPage(),
                'total' => $spaces->total(),
                'last_page' => $spaces->lastPage(),
            ],
            'links' => [
                'prev' => $spaces->previousPageUrl(),
                'next' => $spaces->nextPageUrl(),
            ],
        ]);
    }

    public function pending()
    {
        $spaces = Space::query()
            ->with(['owner', 'features', 'primaryImage'])
            ->where('approval_status', 'pending')
            ->orderByDesc('created_at')
            ->paginate(15);

        return \App\Http\Resources\SpaceResource::collection($spaces);
    }

    public function approve($id)
    {
        $space = Space::findOrFail($id);
        $space->approval_status = 'approved';
        $space->save();

        return response()->json([
            'data' => new \App\Http\Resources\SpaceResource($space->load(['owner', 'features'])),
        ]);
    }

    public function reject($id)
    {
        $space = Space::findOrFail($id);
        $space->approval_status = 'rejected';
        $space->save();

        return response()->json([
            'data' => new \App\Http\Resources\SpaceResource($space->load(['owner', 'features'])),
        ]);
    }
}