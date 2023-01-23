<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Store_asset_order_detail extends Model
{
    protected $table = 'store_asset_order_detail';
    protected $fillable = ['order_id','item_id','initial_picture','new_picture','item_quantity','item_price','status','is_deleted'];
}
