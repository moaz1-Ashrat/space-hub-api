<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SpaceOwner extends Model
{
    use HasFactory;

    protected $fillable = ['id', 'tax_registration_number'];

    public function user()
    {
        return $this->belongsTo(User::class, 'id', 'id');
    }

    public function spaces()
    {
        return $this->hasMany(Space::class, 'user_id', 'id');
    }
    protected $appends = ['tax_registration_number_display'];

    public function getTaxRegistrationNumberDisplayAttribute(): string
     {
    return substr($this->tax_registration_number, 0, 3) . '-' .
           substr($this->tax_registration_number, 3, 3) . '-' .
           substr($this->tax_registration_number, 6, 3);
}
}
