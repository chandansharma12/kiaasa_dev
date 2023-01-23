<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Material_vendor extends Model
{
    protected $table = 'material_vendors';
    protected $fillable = ['material_id','vendor_id','is_deleted'];
}
