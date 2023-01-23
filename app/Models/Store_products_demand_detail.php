<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class store_products_demand_detail extends Model
{
    protected $table = 'store_products_demand_detail';
    protected $fillable = ['store_id','demand_id','product_id','po_item_id','product_quantity','store_intake_qty','fake_inventory','demand_status','status','is_deleted'];
}
