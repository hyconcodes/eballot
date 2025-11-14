<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoterVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'election_id',
        'nin_number',
        'nin_front_path',
        'nin_back_path',
        'voters_card_front_path',
        'voters_card_back_path',
        'state',
        'status',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function election()
    {
        return $this->belongsTo(Election::class);
    }

    public function officer()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}