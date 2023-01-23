<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Store_asset_order extends Model
{
    protected $table = 'store_asset_order';
    protected $fillable = ['user_id','approver_id','store_id','order_status','order_type','order_approve_date','total_amount','comments','total_bill_amount','status','is_deleted'];
}
