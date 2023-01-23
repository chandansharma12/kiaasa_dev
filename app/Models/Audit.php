<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    protected $table = 'audit';
    protected $fillable = ['store_id','audit_no','auditor_id','audit_status','members_present','counter_cash','manual_bills','scan_complete_date','scan_complete_comment','wbc_sku_list','cash_verified','cash_verified_comment',
    'system_inv_quantity','system_inv_cost_price','system_inv_sale_price','store_inv_quantity','store_inv_cost_price','store_inv_sale_price','pos_order_id','wh_inv_quantity','wh_inv_cost_price','wh_inv_sale_price','audit_type','status','is_deleted'];
}
