<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Pos_product_master extends Model
{
    protected $table = 'pos_product_master';
    protected $fillable = ['product_name','product_barcode','product_sku','product_sku_id','vendor_product_sku','category_id','subcategory_id','product_description','base_price','sale_price','size_id','color_id',
   'push_demand_booked','sale_category','story_id','season_id','product_type','hsn_code','user_id','gst_inclusive','custom_product','arnon_product','supplier_id','supplier_name','product_rate_updated',
   'static_product','fake_inventory','status','is_deleted'];
}
