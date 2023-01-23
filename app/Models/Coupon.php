<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $table = 'coupon';
    protected $fillable = ['coupon_name','items_count','store_id','valid_from','valid_to','discount','coupon_type','user_id','status','is_deleted'];
}
