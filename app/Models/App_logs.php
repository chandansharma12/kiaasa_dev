<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class App_logs extends Model
{
    protected $table = 'app_logs';
    protected $fillable = ['log_title','log_key','log_type','user_id','role_id'];
}
