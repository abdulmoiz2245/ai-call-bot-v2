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
    ];

    protected $casts = [
        'scripts' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
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
