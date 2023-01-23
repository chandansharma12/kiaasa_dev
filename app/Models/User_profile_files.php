<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class User_profile_files extends Model
{
    protected $table = 'user_profile_files';
    protected $fillable = ['user_id','file_name','file_title','file_category','file_type','is_deleted'];
}
