<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'order_number',
        'contact_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'status',
        'total_amount',
        'currency',
        'ordered_at',
        'shipping_address',
        'billing_address',
        'custom_fields',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'ordered_at' => 'datetime',
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'custom_fields' => 'array',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
