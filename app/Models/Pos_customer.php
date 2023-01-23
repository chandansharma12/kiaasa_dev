<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Pos_customer extends Model
{
    protected $table = 'pos_customer';
    protected $fillable = ['salutation','customer_name','email','phone','dob','wedding_date','postal_code','fake_inventory','password','status','is_deleted'];
}
