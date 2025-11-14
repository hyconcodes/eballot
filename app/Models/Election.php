<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Election extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'type',
        'start_at',
        'end_at',
        'banner_path',
        'is_paused',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_paused' => 'boolean',
    ];

    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }
}