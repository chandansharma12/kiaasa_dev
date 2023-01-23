<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Purchase_order_items extends Model
{
    protected $table = 'purchase_order_items';
    protected $fillable = ['order_id','product_sku','vendor_sku','item_master_id','product_sku','quotation_detail_id','design_id','width_id','content_id','gsm_id','unit_id','vendor_id',
    'qty_ordered','qty_received','qty_received_actual','qty_defective','qty_returned','rate','cost','order_status','order_comment','size_data','gst_amount','gst_percent','total_cost',
    'size_data_received','fake_inventory','is_deleted'];
}
