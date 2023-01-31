<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class PosCustomerResetPassword extends Model
{
     //use HasFactory;

     protected  $table = "pos_customer_reset_passwords";

     protected $fillable = [
        'email',
        'token',
        'created_at',
        'upfated_at'
    ];
}
