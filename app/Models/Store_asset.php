<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Store_asset extends Model
{
    protected $table = 'store_assets';
    protected $fillable = ['item_name','item_desc','item_manufacturer','base_price','item_type','category_id','subcategory_id','item_status','is_deleted'];
}
