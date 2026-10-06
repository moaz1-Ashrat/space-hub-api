<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class SpaceImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_id',
        'image_path',
        'order',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'order' => 'integer',
    ];

    protected $appends = ['url'];

    /**
     * Relationship: belongs to Space
     */
    public function space()
    {
        return $this->belongsTo(Space::class);
    }

    /**
     * Accessor: full URL for the image
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->image_path);
    }
}
