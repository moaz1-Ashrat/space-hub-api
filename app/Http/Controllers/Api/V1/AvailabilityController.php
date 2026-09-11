<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Space;
use App\Models\Availability;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\AvailabilityResource;
use App\Http\Requests\Availability\StoreAvailabilityRequest;
use App\Http\Requests\Availability\UpdateAvailabilityRequest;

class AvailabilityController extends Controller
{
    public function indexBySpace(Space $space)
    {
        $slots = $space->availabilities()->latest()->get();

        return AvailabilityResource::collection($slots);
    }

    public function store(StoreAvailabilityRequest $request, Space $space)
    {
        abort_unless($request->user()->id === $space->user_id, 403);

        $slot = $space->availabilities()->create($request->validated());

        return response()->json([
            'message' => 'Availability slot created',
            'data' => new AvailabilityResource($slot),
        ], 201);
    }

    public function update(UpdateAvailabilityRequest $request, Availability $availability)
    {
        abort_unless($request->user()->id === $availability->space->user_id, 403);

        $availability->update($request->validated());

        return response()->json([
            'message' => 'Availability slot updated',
            'data' => new AvailabilityResource($availability),
        ]);
    }
}
