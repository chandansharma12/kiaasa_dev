<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Store_user extends Model
{
    protected $table = 'store_users';
    protected $fillable = ['store_id','user_id','status','is_deleted'];
}
