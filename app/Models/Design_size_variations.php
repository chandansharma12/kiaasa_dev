<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Design_size_variations extends Model
{
    protected $table = 'design_size_variations';
    protected $fillable = ['design_items_instance_id','size_id','variation_type','variation_value','status','is_deleted'];
}
