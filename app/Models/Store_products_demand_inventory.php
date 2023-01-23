<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Store_products_demand_inventory extends Model
{
    protected $table = 'store_products_demand_inventory';
    protected $fillable = ['demand_id','inventory_id','product_id','product_sku_id','from_store_id','store_id','po_item_id','vendor_id','transfer_status','receive_status','push_demand_id','vendor_base_price','vendor_gst_percent','vendor_gst_amount','base_price','sale_price','store_base_rate','store_gst_percent','store_gst_amount','store_base_price','transfer_date','receive_date','fake_inventory','demand_status','is_deleted'];
}
