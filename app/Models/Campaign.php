<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'agent_id',
        'created_by',
        'name',
        'description',
        'status',
        'data_source_type',
        'schedule_settings',
        'max_retries',
        'max_concurrency',
        'call_order',
        'record_calls',
        'caller_id',
        'filter_criteria',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'schedule_settings' => 'array',
        'filter_criteria' => 'array',
        'record_calls' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function calls()
    {
        return $this->hasMany(Call::class);
    }

    // Status helpers
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }
}
