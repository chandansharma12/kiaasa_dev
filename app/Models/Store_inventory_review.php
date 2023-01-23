<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Store_inventory_review extends Model
{
    protected $table = 'store_inventory_review';
    protected $fillable = ['base_store_id','inv_id','product_id','store_id','demand_id','po_id','po_item_id','inv_barcode','product_status','inv_status','reason_str','inv_is_deleted','is_deleted'];
}
