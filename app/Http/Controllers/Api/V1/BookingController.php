<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreBookingRequest;
use App\Services\BookingService;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    /**
     * The booking service.
     *
     * @var BookingService
     */
    protected BookingService $service;

    public function __construct(BookingService $service)
    {
        $this->middleware('auth:sanctum');
        $this->service = $service;
    }

    public function store(StoreBookingRequest $request)
    {
        $payload = $request->validated();
        $payload['user_id'] = $request->user()->id;

        $booking = $this->service->createBooking($payload);

        return (new BookingResource($booking->load(['payment', 'space'])))
            ->response()
            ->setStatusCode(201);
    }

    public function customerIndex(Request $request)
    {
        $user = $request->user();
        $q = Booking::with(['payment', 'space'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at');

        $p = $q->paginate(15);

        return BookingResource::collection($p)->response();
    }

    public function ownerIndex(Request $request)
    {
        $user = $request->user();

        $q = Booking::with(['payment', 'space'])
            ->whereHas('space', function ($q2) use ($user) {
                $q2->where('user_id', $user->id);
            })
            ->orderByDesc('created_at');

        $p = $q->paginate(15);

        return BookingResource::collection($p)->response();
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $booking = Booking::with(['payment', 'space'])->findOrFail($id);

        $isBooker = $booking->user_id == $user->id;
        $isOwner = $booking->space && $booking->space->user_id == $user->id;
        $isAdmin = isset($user->role) && $user->role === 'admin';

        if (! ($isBooker || $isOwner || $isAdmin)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return new BookingResource($booking);
    }

    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        $booking = Booking::with('space')->findOrFail($id);

        $isBooker = $booking->user_id == $user->id;
        $isOwner = $booking->space && $booking->space->user_id == $user->id;

        if (! ($isBooker || $isOwner)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (! in_array($booking->booking_status, ['pending', 'confirmed'])) {
            return response()->json(['message' => 'Cannot cancel this booking'], 400);
        }

        $booking->booking_status = 'cancelled';
        $booking->save();

        return new BookingResource($booking->load(['payment', 'space']));
    }

    public function confirm(Request $request, $id)
    {
        $user = $request->user();
        $booking = Booking::with('space')->findOrFail($id);

        $isOwner = $booking->space && $booking->space->user_id == $user->id;
        if (! $isOwner) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($booking->booking_status !== 'pending') {
            return response()->json(['message' => 'Only pending bookings can be confirmed'], 400);
        }

        $booking->booking_status = 'confirmed';
        $booking->save();

        return new BookingResource($booking->load(['payment', 'space']));
    }
}
