<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Pos_customer_orders_update extends Model
{
    protected $table = 'pos_customer_orders_update';
    protected $fillable = ['store_id','orders_count','order_date','is_deleted'];
}
