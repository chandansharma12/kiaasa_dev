<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class User_roles extends Model
{
    protected $table = 'user_roles';
    protected $fillable = ['role_name','role_status'];
}
