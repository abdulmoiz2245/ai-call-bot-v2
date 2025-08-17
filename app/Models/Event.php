<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'type',
        'subject_type',
        'subject_id',
        'data',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'data' => 'array',
        'metadata' => 'array',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }
}
