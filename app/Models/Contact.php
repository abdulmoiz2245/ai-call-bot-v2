<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'campaign_id',
        'first_name',
        'last_name',
        'name',
        'email',
        'phone',
        'tags',
        'segment',
        'locale',
        'status',
        'is_dnc',
        'opted_out_at',
        'opt_out_reason',
        'custom_fields',
        'last_contacted_at',
        'notes',
    ];

    protected $casts = [
        'tags' => 'array',
        'custom_fields' => 'array',
        'is_dnc' => 'boolean',
        'opted_out_at' => 'datetime',
        'last_contacted_at' => 'datetime',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function calls()
    {
        return $this->hasMany(Call::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Status helpers
    public function isNew(): bool
    {
        return $this->status === 'new';
    }

    public function isQueued(): bool
    {
        return $this->status === 'queued';
    }

    public function isCalling(): bool
    {
        return $this->status === 'calling';
    }

    public function isCalled(): bool
    {
        return $this->status === 'called';
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isOptedOut(): bool
    {
        return $this->status === 'opted_out';
    }
}
