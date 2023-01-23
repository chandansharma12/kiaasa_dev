<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Store_inventory_balance extends Model
{
    protected $table = 'store_inventory_balance';
    protected $fillable = ['inv_date','record_type','store_id','category_id','bal_qty','bal_value','is_deleted'];
}
