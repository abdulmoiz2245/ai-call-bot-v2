<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Call extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'campaign_id',
        'contact_id',
        'agent_id',
        'external_call_id',
        'from_number',
        'to_number',
        'status',
        'duration',
        'cost',
        'recording_url',
        'metadata',
        'gateway_data',
        'started_at',
        'answered_at',
        'ended_at',
        'failure_reason',
        'retry_count',
        'call_sid',
        'created_by',
    ];

    protected $casts = [
        'cost' => 'decimal:4',
        'metadata' => 'array',
        'gateway_data' => 'array',
        'started_at' => 'datetime',
        'answered_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    // Status helpers
    public function isQueued(): bool
    {
        return $this->status === 'queued';
    }

    public function isRinging(): bool
    {
        return $this->status === 'ringing';
    }

    public function isAnswered(): bool
    {
        return $this->status === 'answered';
    }

    public function isVoicemail(): bool
    {
        return $this->status === 'voicemail';
    }

    public function isBusy(): bool
    {
        return $this->status === 'busy';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isNoAnswer(): bool
    {
        return $this->status === 'no_answer';
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, ['answered', 'voicemail', 'busy', 'failed', 'no_answer']);
    }
}
