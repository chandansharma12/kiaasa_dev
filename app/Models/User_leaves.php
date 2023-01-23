<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class User_leaves extends Model
{
    protected $table = 'user_leaves';
    protected $fillable = ['user_id','from_date','to_date','comments','leave_status','leave_type','is_deleted'];
}
