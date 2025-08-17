<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_type_id',
        'name',
        'slug',
        'email',
        'phone',
        'address',
        'timezone',
        'business_hours',
        'call_settings',
        'provider_settings',
        'compliance_settings',
        'default_language',
        'currency',
        'is_active',
        'trial_ends_at',
    ];

    protected $casts = [
        'business_hours' => 'array',
        'call_settings' => 'array',
        'provider_settings' => 'array',
        'compliance_settings' => 'array',
        'is_active' => 'boolean',
        'trial_ends_at' => 'datetime',
    ];

    // Relationships
    public function companyType()
    {
        return $this->belongsTo(CompanyType::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function agents()
    {
        return $this->hasMany(Agent::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function calls()
    {
        return $this->hasMany(Call::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
