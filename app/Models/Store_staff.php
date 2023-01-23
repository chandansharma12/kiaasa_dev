<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Store_staff extends Model
{
    protected $table = 'store_staff';
    protected $fillable = ['store_id','name','phone_no','address','status','is_deleted'];
}
