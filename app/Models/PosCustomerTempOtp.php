<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class PosCustomerTempOtp extends Model
{
    protected $table = 'pos_customer_temp_opt_save';
    protected $fillable = ['phone','otp','created_at','updated_at'];
}
