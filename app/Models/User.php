<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';
    protected $fillable = ['name','email','password','user_type','parent_user','other_roles','api_token','api_token_created_at','store_owner','is_view_modified_inv','status','is_deleted'];
}
