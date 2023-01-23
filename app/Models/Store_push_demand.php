<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Store_push_demand extends Model
{
    protected $table = 'store_push_demand';
    protected $fillable = ['demand_status','user_id','comments','status','is_deleted'];
}
