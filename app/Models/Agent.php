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

    /**
     * Extract variables from text with {{variable_name}} pattern
     */
    public function extractVariables(string $text): array
    {
        preg_match_all('/\{\{([^}]+)\}\}/', $text, $matches);
        return array_unique($matches[1]);
    }

    /**
     * Get all variables used in system prompt and greeting message
     */
    public function getAllVariables(): array
    {
        $variables = [];
        
        if ($this->system_prompt) {
            $variables = array_merge($variables, $this->extractVariables($this->system_prompt));
        }
        
        if ($this->greeting_message) {
            $variables = array_merge($variables, $this->extractVariables($this->greeting_message));
        }
        
        return array_unique($variables);
    }

    /**
     * Replace variables in text with provided values
     */
    public function replaceVariables(string $text, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $text = str_replace("{{$key}}", $value, $text);
        }
        return $text;
    }

    /**
     * Get processed system prompt with variables replaced
     */
    public function getProcessedSystemPrompt(array $variables = []): string
    {
        if (!$this->system_prompt) {
            return '';
        }
        
        return $this->replaceVariables($this->system_prompt, $variables);
    }

    /**
     * Get processed greeting message with variables replaced
     */
    public function getProcessedGreetingMessage(array $variables = []): string
    {
        if (!$this->greeting_message) {
            return '';
        }
        
        return $this->replaceVariables($this->greeting_message, $variables);
    }
}
