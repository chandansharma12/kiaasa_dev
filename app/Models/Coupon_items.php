<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Coupon_items extends Model
{
    protected $table = 'coupon_items';
    protected $fillable = ['coupon_id','coupon_no','coupon_used','order_id','is_deleted'];
}
