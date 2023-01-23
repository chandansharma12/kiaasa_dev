<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class User_overtime extends Model
{
    protected $table = 'user_overtime';
    protected $fillable = ['user_id','overtime_date','overtime_hours','overtime_status','comments','is_deleted'];
}
