<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Pos_customer_orders_payments extends Model
{
    protected $table = 'pos_customer_orders_payments';
    protected $fillable = ['order_id','store_id','payment_method','payment_amount','payment_received','reference_number','fake_inventory','foc','is_deleted'];
}

