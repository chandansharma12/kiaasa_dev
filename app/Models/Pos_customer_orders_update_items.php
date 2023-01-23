<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Pos_customer_orders_update_items extends Model
{
    protected $table = 'pos_customer_orders_update_items';
    protected $fillable = ['update_id','order_id','order_no_prev','order_no_new','update_type','is_deleted'];
}
