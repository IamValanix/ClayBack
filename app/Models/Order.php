<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'customer_email',
        'amount',
        'currency',
        'status',
        'payment_gateway',
        'payment_id'
    ];

    // Una orden tiene un ticket (o varios, pero empecemos con uno)
    public function ticket()
    {
        return $this->hasOne(Ticket::class);
    }
}
