<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Vendor_detail extends Model
{
    protected $table = 'vendor_detail';
    protected $fillable = ['user_id','name','email','phone','address','city','state','postal_code','gst_no','vendor_code','ecommerce_status','pid','pid_added_date','subvendors_count','status','is_deleted'];
}
