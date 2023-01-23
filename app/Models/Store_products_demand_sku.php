<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Store_products_demand_sku extends Model
{
    protected $table = 'store_products_demand_sku';
    protected $fillable = ['demand_id','prod_id','prod_name','prod_hsn_code','prod_sku','prod_quantity','prod_rate','taxable_value','gst_percent','gst_amount','cgst_percent','cgst_amount','sgst_percent','sgst_amount','igst_percent','igst_amount','total_value','fake_inventory','demand_status','is_deleted'];
}
