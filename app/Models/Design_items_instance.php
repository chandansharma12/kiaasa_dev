<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Design_items_instance extends Model
{
    protected $table = 'design_items_instance';
    protected $fillable = ['design_id','design_item_id','design_type_id','body_part_id','width','avg','rate','cost','unit_id','status','is_deleted','image_name','comments','size','qty','fabric_instance_id','role_id','pid'];
}
