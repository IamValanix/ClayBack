<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'ticket_code', 'is_used'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
