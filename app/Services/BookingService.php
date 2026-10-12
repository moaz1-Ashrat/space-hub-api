<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Space;
use App\Models\Coupon;
use App\Models\Availability;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RuntimeException;
use InvalidArgumentException;

class BookingService
{
    protected string  $bookingModel;
    protected string  $paymentModel;
    protected string  $spaceModel;
    protected string  $availabilityModel;
    protected string  $userModel;
    protected ?string $couponModel;

    public function __construct(
        object|string|null $booking = null,
        object|string|null $payment = null,
        object|string|null $space = null,
        object|string|null $coupon = null,
        object|string|null $availability = null,
        object|string|null $user = null
    ) {
        $this->bookingModel      = $this->resolveModelClass($booking ?? Booking::class);
        $this->paymentModel      = $this->resolveModelClass($payment ?? Payment::class);
        $this->spaceModel        = $this->resolveModelClass($space ?? Space::class);
        $this->availabilityModel = $this->resolveModelClass($availability ?? Availability::class);
        $this->userModel         = $this->resolveModelClass($user ?? User::class);

        $couponClass = $this->resolveModelClass($coupon ?? Coupon::class);
        $this->couponModel = class_exists($couponClass) ? $couponClass : null;
    }

    protected function resolveModelClass(object|string $model): string
    {
        return is_object($model) ? get_class($model) : $model;
    }

    public function createBooking(array $data): Booking
    {
        $startInput = $data['start_datetime']
            ?? trim(($data['booking_date'] ?? '') . ' ' . ($data['start_time'] ?? ''));
        $endInput   = $data['end_datetime']
            ?? trim(($data['booking_date'] ?? '') . ' ' . ($data['end_time'] ?? ''));

        if (empty($data['user_id']) || empty($data['space_id'])
            || empty(trim($startInput)) || empty(trim($endInput))) {
            throw new InvalidArgumentException('Missing required booking data.');
        }

        try {
            $start = Carbon::parse($startInput);
            $end   = Carbon::parse($endInput);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException('Invalid date/time format: ' . $e->getMessage());
        }

        if ($end->lte($start)) {
            throw new InvalidArgumentException('end_time must be after start_time.');
        }

        if ($start->isPast()) {
            throw new InvalidArgumentException('Cannot create a booking in the past.');
        }

        if (! $start->isSameDay($end)) {
            throw new InvalidArgumentException('Bookings must start and end on the same day.');
        }

        $userId     = (int) $data['user_id'];
        $spaceId    = (int) $data['space_id'];
        $couponCode = $data['coupon_code'] ?? null;
        $couponId   = $data['coupon_id']   ?? null;

        return DB::transaction(function () use (
            $userId, $spaceId, $start, $end, $couponCode, $couponId
        ) {
            $bookingClass = $this->bookingModel;
            $spaceClass   = $this->spaceModel;
            $userClass    = $this->userModel;

            $bookingDate = $start->toDateString();
            $startTime   = $start->toTimeString();
            $endTime     = $end->toTimeString();

            if (! ($userClass)::whereKey($userId)->exists()) {
                throw new ModelNotFoundException("User not found: {$userId}");
            }

            $space = ($spaceClass)::whereKey($spaceId)->lockForUpdate()->first();
            if (! $space) {
                throw new ModelNotFoundException("Space not found: {$spaceId}");
            }
            if (! ($space->is_active ?? true)) {
                throw new RuntimeException('This space is not available for booking.');
            }

            $overlap = ($bookingClass)::where('space_id', $spaceId)
                ->whereIn('booking_status', ['pending', 'confirmed'])
                ->whereDate('booking_date', $bookingDate)
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
                })
                ->lockForUpdate()
                ->first();

            if ($overlap) {
                throw new RuntimeException('Requested time slot is already booked.');
            }

            $this->validateAvailability($spaceId, $start, $end);

            $minutes      = $start->diffInMinutes($end);
            $hours        = $minutes / 60;
            $pricePerHour = (float) ($space->price_per_hour ?? 0.0);
            $totalAmount  = round($pricePerHour * $hours, 2);

            $baseCommissionRate = 0.10;
            $commissionRate     = $baseCommissionRate;
            $coupon             = null;
            $couponDiscount     = 0.0;

            if ($couponCode || $couponId) {
                if ($this->couponModel === null) {
                    throw new RuntimeException('Coupon handling requested but Coupon model is not present.');
                }

                $couponClass = $this->couponModel;
                $now = now();

                $couponQuery = ($couponClass)::query()
                    ->where('is_used', false)
                    ->where(function ($q) use ($now) {
                        $q->whereNull('expiry_date')->orWhere('expiry_date', '>', $now);
                    })
                    ->where(function ($q) use ($userId) {
                        $q->where('user_id', $userId)->orWhereNull('user_id');
                    });

                if ($couponCode) {
                    $couponQuery->where('code', $couponCode);
                } else {
                    $couponQuery->whereKey($couponId);
                }

                $coupon = $couponQuery->lockForUpdate()->first();

                if (! $coupon) {
                    throw new RuntimeException('Invalid or expired coupon code.');
                }

                $couponDiscount = max(0.0, (float) ($coupon->discount_value ?? 0.0));

                if (isset($coupon->commission_rate) && $coupon->commission_rate !== null) {
                    $commissionRate = (float) $coupon->commission_rate;
                }
            }

            $customerPaid     = max(round($totalAmount - $couponDiscount, 2), 0.00);
            $commissionAmount = round($totalAmount * $commissionRate, 2);
            $ownerPayout      = round($totalAmount - $commissionAmount, 2);

            $paymentClass = $this->paymentModel;
            $payment = ($paymentClass)::create([
                'amount'             => $customerPaid,
                'payment_methode'    => null,
                'payment_status'     => 'pending',
                'payment_date_time'  => null,
            ]);

            if (! $payment || ! $payment->id) {
                throw new RuntimeException('Failed to create payment record.');
            }

            $booking = ($bookingClass)::create([
                'user_id'             => $userId,
                'space_id'            => $spaceId,
                'payment_id'          => $payment->id,
                'booking_date'        => $bookingDate,
                'start_time'          => $startTime,
                'end_time'            => $endTime,
                'total_amount'        => $totalAmount,
                'commission_rate'     => $commissionRate,
                'commission_amount'   => $commissionAmount,
                'customer_paid'       => $customerPaid,
                'owner_payout'        => $ownerPayout,
                'booking_status'      => 'pending',
                'attendance_status'   => 'pending',
                'historical_booking'  => false,
            ]);

            if (! $booking || ! $booking->id) {
                throw new RuntimeException('Failed to create booking record.');
            }

            if ($coupon) {
                $coupon->is_used    = true;
                $coupon->used_at    = now();
                $coupon->booking_id = $booking->id;
                $coupon->save();
            }

            return $booking->load(['payment', 'space']);
        }, 5);
    }

    public function cancelBooking(int $bookingId, ?string $reason = null): Booking
    {
        return DB::transaction(function () use ($bookingId, $reason) {
            $bookingClass = $this->bookingModel;
            $booking = ($bookingClass)::whereKey($bookingId)->lockForUpdate()->first();

            if (! $booking) {
                throw new ModelNotFoundException("Booking not found: {$bookingId}");
            }

            if (in_array($booking->booking_status, ['cancelled', 'completed'], true)) {
                throw new RuntimeException("Cannot cancel a booking with status: {$booking->booking_status}");
            }

            $booking->booking_status = 'cancelled';
            $booking->save();

            if ($this->couponModel) {
                $couponClass = $this->couponModel;
                $coupon = ($couponClass)::where('booking_id', $booking->id)
                    ->lockForUpdate()
                    ->first();

                if ($coupon) {
                    $coupon->is_used    = false;
                    $coupon->used_at    = null;
                    $coupon->booking_id = null;
                    $coupon->save();
                }
            }

            return $booking;
        }, 5);
    }

    protected function validateAvailability(int $spaceId, Carbon $start, Carbon $end): void
    {
        $availabilityClass = $this->availabilityModel;

        $hasAnyAvailability = ($availabilityClass)::where('space_id', $spaceId)->exists();
        if (! $hasAnyAvailability) {
            return;
        }

        $dayOfWeek = (int) $start->dayOfWeek;
        $date      = $start->toDateString();
        $startTime = $start->toTimeString();
        $endTime   = $end->toTimeString();

        $hasSlot = ($availabilityClass)::where('space_id', $spaceId)
            ->where('is_available', true)
            ->where(function ($q) use ($dayOfWeek, $date) {
                $q->where(function ($q2) use ($dayOfWeek) {
                    $q2->where('day_of_week', $dayOfWeek)
                       ->whereNull('special_date');
                })->orWhere('special_date', $date);
            })
            ->where('start_time', '<=', $startTime)
            ->where('end_time', '>=', $endTime)
            ->exists();

        if (! $hasSlot) {
            throw new RuntimeException(
                'The requested time is outside the space availability hours. Please check the weekly schedule.'
            );
        }
    }
}
