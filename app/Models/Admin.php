<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Admin extends Model
{
    use HasFactory;

    protected $fillable = ['id', 'level_of_authority'];

    public function user()
    {
        return $this->belongsTo(User::class, 'id', 'id');
    }
}
