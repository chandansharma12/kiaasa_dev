<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Store_sku_inventory_balance extends Model
{
    protected $table = 'store_sku_inventory_balance';
    public $timestamps = false;
    protected $fillable = ['inv_date','record_type','store_id','sku','bal_qty','bal_value','price'];
}
