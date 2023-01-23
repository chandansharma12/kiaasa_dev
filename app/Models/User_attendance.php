<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class User_attendance extends Model
{
    protected $table = 'user_attendance';
    protected $fillable = ['user_id','attendance_date','attendance_status','is_deleted'];
}
