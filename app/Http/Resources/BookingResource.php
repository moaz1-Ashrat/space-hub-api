<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BookingResource extends JsonResource
{
    protected function canViewInternals()
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        $isOwner = $this->space && isset($this->space->user_id) && $this->space->user_id == $user->id;
        $isAdmin = isset($user->role) && $user->role === 'admin';

        return $isOwner || $isAdmin;
    }

    public function toArray($request)
    {
        $startIso = null;
        $endIso = null;

        if ($this->booking_date && $this->start_time) {
            $startIso = Carbon::parse(
                $this->booking_date->format('Y-m-d') . ' ' . $this->start_time
            )->toIso8601String();
        }
        if ($this->booking_date && $this->end_time) {
            $endIso = Carbon::parse(
                $this->booking_date->format('Y-m-d') . ' ' . $this->end_time
            )->toIso8601String();
        }

        $base = [
            'id' => $this->id,
            'space' => [
                'id' => $this->space->id ?? null,
                'name' => $this->space->name ?? null,
            ],
            'start_datetime' => $startIso,
            'end_datetime' => $endIso,
            'total_amount' => (float) $this->total_amount,
            'customer_paid' => (float) ($this->customer_paid ?? 0.0),
            'payment' => $this->whenLoaded('payment') ? [
                'id' => $this->payment->id,
                'amount' => (float) $this->payment->amount,
                'payment_status' => $this->payment->payment_status,
            ] : null,
            'booking_status' => $this->booking_status,
            'created_at' => $this->created_at,
        ];

        if ($this->canViewInternals()) {
            $base['commission_rate'] = (float) ($this->commission_rate ?? 0.0);
            $base['commission_amount'] = (float) ($this->commission_amount ?? 0.0);
            $base['owner_payout'] = (float) ($this->owner_payout ?? 0.0);
        }

        return $base;
    }
}
