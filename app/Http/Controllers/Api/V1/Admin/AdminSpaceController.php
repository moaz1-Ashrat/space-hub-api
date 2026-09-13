<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SpaceResource;
use App\Models\Space;

class AdminSpaceController extends Controller
{
    public function pending()
    {
        $spaces = Space::query()
            ->with(['owner', 'features'])
            ->where('approval_status', 'pending')
            ->orderByDesc('created_at')
            ->paginate(15);

        return SpaceResource::collection($spaces);
    }

    public function approve($id)
    {
        $space = Space::findOrFail($id);
        $space->approval_status = 'approved';
        $space->save();

        return response()->json([
            'data' => new SpaceResource($space->load(['owner', 'features'])),
        ]);
    }

    public function reject($id)
    {
        $space = Space::findOrFail($id);
        $space->approval_status = 'rejected';
        $space->save();

        return response()->json([
            'data' => new SpaceResource($space->load(['owner', 'features'])),
        ]);
    }
}
