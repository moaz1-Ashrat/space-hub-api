<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Feature extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'category', 'description'];

    public function spaces()
    {
        return $this->belongsToMany(Space::class, 'space_feature', 'feature_id', 'space_id');
    }
}
