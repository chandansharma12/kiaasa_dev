<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Pos_customer_orders_detail extends Model
{
    protected $table = 'pos_customer_orders_detail';
    protected $fillable = ['order_id','store_id','product_id','product_sku_id','po_item_id','inventory_id','vendor_id','product_quantity','base_price','sale_price','net_price','discounted_price','discounted_price_actual','discount_percent','discount_amount','discount_amount_actual','gst_percent','gst_amount','gst_inclusive','discount_id','staff_id','other_store_product','arnon_prod_inv','coupon_item_id','coupon_discount_percent','bill_product_type','bill_product_group_id','bill_product_group_name','fake_inventory','foc','status','is_deleted'];
}
