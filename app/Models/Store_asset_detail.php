<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Store_asset_detail extends Model
{
    protected $table = 'store_asset_detail';
    protected $fillable = ['item_id','region_id','price','item_status','is_deleted'];
}
