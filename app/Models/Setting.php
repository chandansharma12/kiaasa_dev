<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'app_settings';
    protected $fillable = ['setting_key','setting_value','from_date','to_date'];
}
