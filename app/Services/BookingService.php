<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Space;
use App\Models\Coupon;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RuntimeException;
use InvalidArgumentException;

class BookingService
{
    /** @var object|string|null */
    protected object|string|null $bookingModel;

    /** @var object|string|null */
    protected object|string|null $paymentModel;

    /** @var object|string|null */
    protected object|string|null $spaceModel;

    /** @var object|string|null */
    protected object|string|null $couponModel;

    public function __construct(object|string|null $booking = null, object|string|null $payment = null, object|string|null $space = null, object|string|null $coupon = null)
    {
        $this->bookingModel = $booking ?? Booking::class;
        $this->paymentModel = $payment ?? Payment::class;
        $this->spaceModel   = $space ?? Space::class;
        $this->couponModel  = $coupon ?? (class_exists(Coupon::class) ? Coupon::class : null);
    }

    /**
     * Create a booking (wrapped in DB transaction).
     *
     * Expected $data keys:
     *  - user_id
     *  - space_id
     *  - start_datetime OR booking_date + start_time
     *  - end_datetime OR booking_date + end_time
     *  - coupon_id (optional)
     *
     * @throws \Throwable
     */
    public function createBooking(array $data): Booking
    {
        $startInput = $data['start_datetime'] ?? (($data['booking_date'] ?? '') . ' ' . ($data['start_time'] ?? ''));
        $endInput   = $data['end_datetime'] ?? (($data['booking_date'] ?? '') . ' ' . ($data['end_time'] ?? ''));

        if (empty($data['user_id']) || empty($data['space_id']) || empty(trim($startInput)) || empty(trim($endInput))) {
            throw new InvalidArgumentException('Missing required booking data.');
        }

        $userId  = (int) $data['user_id'];
        $spaceId = (int) $data['space_id'];

        $start = Carbon::parse($startInput);
        $end   = Carbon::parse($endInput);

        if ($end->lte($start)) {
            throw new InvalidArgumentException('end_time must be after start_time.');
        }

        // Load space
        $space = ($this->spaceModel)::find($spaceId);
        if (! $space) {
            throw new ModelNotFoundException("Space not found: {$spaceId}");
        }

        $bookingDate = $start->toDateString();
        $startTime   = $start->toTimeString();
        $endTime     = $end->toTimeString();

        // 1) Check overlapping bookings on same space (pending/confirmed)
        $overlap = ($this->bookingModel)::where('space_id', $spaceId)
            ->whereIn('booking_status', ['pending', 'confirmed'])
            ->whereDate('booking_date', $bookingDate)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                  ->where('end_time', '>', $startTime);
            })
            ->exists();

        if ($overlap) {
            throw new RuntimeException('Requested time slot is already booked.');
        }

        // 2) Calculate total_amount from price_per_hour and duration (hours)
        $minutes = abs($end->diffInMinutes($start));
        $hours = $minutes / 60;
        $pricePerHour = (float) ($space->price_per_hour ?? 0.0);
        $totalAmount = round($pricePerHour * $hours, 2);

        // Defaults
        $baseCommissionRate = 0.10;
        $commissionRate = $baseCommissionRate;
        $coupon = null;
        $couponDiscount = 0.0;

        // 3) Coupon logic if provided
        if (! empty($data['coupon_id'])) {
            if ($this->couponModel === null || ! class_exists(is_object($this->couponModel) ? get_class($this->couponModel) : $this->couponModel)) {
                throw new RuntimeException('Coupon handling requested but Coupon model is not present.');
            }

            $couponClass = is_object($this->couponModel) ? get_class($this->couponModel) : $this->couponModel;

            $couponModel = ($couponClass)::where('id', $data['coupon_id'])
                ->where('user_id', $userId) // coupons.user_id now
                ->where('is_used', false)
                ->where(function ($q) {
                    $q->whereNull('expiry_date')->orWhere('expiry_date', '>', now());
                })
                ->first();

            if (! $couponModel) {
                throw new RuntimeException('Invalid/expired coupon or not owned by user.');
            }

            $coupon = $couponModel;
            $couponDiscount = max(0.0, (float) ($coupon->discount_value ?? 0.0));

            if (isset($coupon->commission_rate) && $coupon->commission_rate !== null) {
                $commissionRate = (float) $coupon->commission_rate;
            }
        } else {
            // optional subscription stub
            $user = User::find($userId);
            if ($user && method_exists($user, 'hasActiveSubscription') && $user->hasActiveSubscription()) {
                // integrate subscription-based behavior here (if desired)
            }
        }

        // 4) DB transaction: create Payment first, then Booking (persist commission fields)
        return DB::transaction(function () use ($userId, $spaceId, $bookingDate, $startTime, $endTime, $totalAmount, $commissionRate, $coupon, $couponDiscount) {
            $customerPaid = max(round($totalAmount - $couponDiscount, 2), 0.00);
            $commissionAmount = round($totalAmount * $commissionRate, 2);
            $ownerPayout = round($totalAmount - $commissionAmount, 2);

            // create Payment FIRST
            $paymentClass = is_object($this->paymentModel) ? get_class($this->paymentModel) : $this->paymentModel;
            $payment = ($paymentClass)::create([
                'amount' => $customerPaid,
                'payment_methode' => null,
                'payment_status' => 'pending',
                'payment_date_time' => null,
            ]);

            if (! $payment || ! $payment->id) {
                throw new RuntimeException('Failed to create payment record.');
            }

            // create Booking and persist commission fields
            $bookingClass = is_object($this->bookingModel) ? get_class($this->bookingModel) : $this->bookingModel;
            $booking = ($bookingClass)::create([
                'user_id' => $userId,
                'space_id' => $spaceId,
                'payment_id' => $payment->id,
                'booking_date' => $bookingDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'total_amount' => $totalAmount,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'customer_paid' => $customerPaid,
                'owner_payout' => $ownerPayout,
                'booking_status' => 'pending',
                'attendance_status' => 'pending',
                'historical_booking' => false,
            ]);

            if (! $booking || ! $booking->id) {
                throw new RuntimeException('Failed to create booking record.');
            }

            // mark coupon used
            if ($coupon) {
                $coupon->is_used = true;
                $coupon->used_at = now();
                $coupon->booking_id = $booking->id;
                $coupon->save();
            }

            return $booking->load(['payment', 'space']);
        }, 5);
    }
}
