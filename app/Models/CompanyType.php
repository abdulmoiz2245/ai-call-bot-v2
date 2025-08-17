<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompanyType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'default_settings',
        'is_active',
    ];

    protected $casts = [
        'default_settings' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function companies()
    {
        return $this->hasMany(Company::class);
    }
}
