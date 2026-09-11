<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'gender',
        'role',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed', // bcrypt automatically
        ];
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn () => "{$this->first_name} {$this->last_name}");
    }

    public function customer()
    {
        return $this->hasOne(Customer::class, 'id', 'id');
    }

    public function spaceOwner()
    {
        return $this->hasOne(SpaceOwner::class, 'id', 'id');
    }

    public function admin()
    {
        return $this->hasOne(Admin::class, 'id', 'id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'user_id');
    }

    public function ownedSpaces()
    {
        return $this->hasMany(Space::class, 'user_id');
    }
}
