<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Pos_product_master_inventory extends Model
{
    protected $table = 'pos_product_master_inventory';
    protected $fillable = ['product_master_id','store_id','demand_id','po_id','po_item_id','vendor_id','peice_barcode','product_status','vendor_base_price','vendor_gst_percent','vendor_gst_amount','base_price','sale_price','store_base_rate','product_sku_id',
    'store_gst_percent','store_gst_amount','store_base_price','customer_order_id','intake_date','store_assign_date','store_intake_date','store_sale_date','grn_id','qc_id','qc_status','qc_date','po_detail_id','arnon_inventory','process_inv','fake_inventory','payment_status','payment_id','status','is_deleted'];
}
