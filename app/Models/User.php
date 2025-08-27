<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
        'role',
        'is_active',
        'last_login_at',
        'permissions',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function createdCampaigns()
    {
        return $this->hasMany(Campaign::class, 'created_by');
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    // Role helpers
    public function isSuperAdmin(): bool
    {
        return $this->role === 'PARENT_SUPER_ADMIN';
    }

    public function isCompanyAdmin(): bool
    {
        return $this->role === 'COMPANY_ADMIN';
    }

    public function isAgent(): bool
    {
        return $this->role === 'AGENT';
    }

    public function isViewer(): bool
    {
        return $this->role === 'VIEWER';
    }

    // Permission helpers
    public function hasAccessToAgent($agentId): bool
    {
        // Super admins have access to all agents
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Find the agent
        $agent = Agent::find($agentId);
        if (!$agent) {
            return false;
        }

        // Users can only access agents from their own company
        return $this->company_id === $agent->company_id;
    }
}
