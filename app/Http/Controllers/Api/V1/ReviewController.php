<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Booking;
use App\Models\Review;
use App\Models\Space;
use Illuminate\Database\QueryException;

class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request)
    {
        $data = $request->validated();
        $userId = $request->user()->id;

        $hasCompleted = Booking::where('user_id', $userId)
            ->where('space_id', $data['space_id'])
            ->where('booking_status', 'completed')
            ->exists();

        if (! $hasCompleted) {
            return response()->json([
                'message' => 'You can only review spaces you have completed bookings on',
            ], 403);
        }

        try {
            $review = Review::create([
                'customer_id' => $userId,
                'space_id' => $data['space_id'],
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
                'review_date' => now()->toDateString(),
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'You already reviewed this space',
            ], 422);
        }

        $review->load('customer.user');

        return response()->json([
            'data' => new ReviewResource($review),
        ], 201);
    }

    public function indexBySpace(Space $space)
    {
        $reviews = $space->reviews()
            ->with('customer.user')
            ->orderByDesc('review_date')
            ->paginate(15);

        return ReviewResource::collection($reviews);
    }
}
