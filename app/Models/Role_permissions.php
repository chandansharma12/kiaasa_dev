<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Role_permissions extends Model
{
    protected $table = 'role_permissions';
    protected $fillable = ['route_path','role_id'];
}
