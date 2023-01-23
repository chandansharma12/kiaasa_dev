<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Pos_inventory_payments_detail extends Model
{
    protected $table = 'pos_inventory_payments_detail';
    protected $fillable = ['payment_id','inventory_id','net_price','cost_price','order_id','is_deleted'];
}
