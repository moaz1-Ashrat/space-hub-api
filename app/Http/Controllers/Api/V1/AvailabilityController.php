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
        $slots = $space->availabilities()->orderBy('day_of_week')->orderBy('start_time')->get();

        return AvailabilityResource::collection($slots);
    }

    public function store(StoreAvailabilityRequest $request, Space $space)
    {
        abort_unless($request->user()->id === $space->user_id, 403);

        $data = $request->validated();

        // ✅ Check for duplicate slot (same day + times)
        $exists = $space->availabilities()
            ->where('day_of_week', $data['day_of_week'])
            ->where('start_time', $data['start_time'])
            ->where('end_time', $data['end_time'])
            ->whereNull('special_date')
            ->when(isset($data['special_date']) && $data['special_date'], function ($q) use ($data) {
                $q->where('special_date', $data['special_date']);
            })
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'A slot with the same day and time already exists.',
            ], 422);
        }

        $slot = $space->availabilities()->create($data);

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
            'data' => new AvailabilityResource($availability->fresh()),
        ]);
    }

    /**
     * ✅ NEW: Delete an availability slot.
     */
    public function destroy(Request $request, Availability $availability)
    {
        abort_unless($request->user()->id === $availability->space->user_id, 403);

        $availability->delete();

        return response()->json([
            'message' => 'Availability slot deleted',
        ]);
    }
}