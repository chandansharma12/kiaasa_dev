<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Store_asset_bills extends Model
{
    protected $table = 'store_asset_bills';
    protected $fillable = ['order_id','bill_amount','bill_picture','bill_status','payment_method','vendor_bank_name','vendor_bank_acc_no','vendor_bank_ifsc_code','vendor_bank_cust_name',
    'vendor_bank_acc_type','status','is_deleted'];
}
