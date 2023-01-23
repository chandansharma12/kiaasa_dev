<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Roles_permissions extends Model
{
    protected $table = 'roles_permissions';
    protected $fillable = ['role_id','permission_id','status','is_deleted'];
}
