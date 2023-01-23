<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Pos_customer_orders extends Model
{
    protected $table = 'pos_customer_orders';
    protected $fillable = ['order_no','orig_order_no','customer_id','store_id','payment_method','store_user_id','total_price','reference_no','total_items','order_source','customer_gst_no','status','is_deleted','voucher_amount','voucher_comment','voucher_approver_id','order_type','coupon_item_id','order_status','cancel_comments','cancel_date','cancel_user_id','fake_inventory','foc','bags_count','address_id','bill_data_same','bill_cust_name','bill_address','bill_locality','bill_city_name','bill_postal_code','bill_state_id','pdf_file','rp_order_id','rp_payment_id','rp_signature','bill_top_text','bill_bottom_text','store_gst_no','store_gst_name','store_info_type_1','invoice_series_type'];
}
