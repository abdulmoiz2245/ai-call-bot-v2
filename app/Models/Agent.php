<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Agent extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'created_by',
        'name',
        'description',
        'role',
        'tone',
        'persona',
        'voice_id',
        'language',
        'scripts',
        'settings',
        'is_active',
        'system_prompt',
        'greeting_message',
        'closing_message',
        'voice_settings',
        'transfer_conditions',
        'conversation_flow',
        'elevenlabs_agent_id',
        'is_elevenlabs_connected',
        'elevenlabs_settings',
        'elevenlabs_last_synced',
    ];

    protected $casts = [
        'scripts' => 'array',
        'settings' => 'array',
        'voice_settings' => 'array',
        'transfer_conditions' => 'array',
        'conversation_flow' => 'array',
        'elevenlabs_settings' => 'array',
        'is_active' => 'boolean',
        'is_elevenlabs_connected' => 'boolean',
        'elevenlabs_last_synced' => 'datetime',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function calls()
    {
        return $this->hasMany(Call::class);
    }
}
