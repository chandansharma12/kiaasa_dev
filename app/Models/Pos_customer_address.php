<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Pos_customer_address extends Model
{
    protected $table = 'pos_customer_address';
    protected $fillable = ['full_name','customer_id','address','locality','city_name','postal_code','state_id','is_deleted'];
}
