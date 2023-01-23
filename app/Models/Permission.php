<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'permissions';
    protected $fillable = ['route_path','route_key','description','permission_status','permission_type','is_deleted'];
}
