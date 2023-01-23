<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Design_item_master extends Model
{
    protected $table = 'design_item_master';
    protected $fillable = ['type_id','name_id','quality_id','color_id','content_id','gsm_id','width_id','unit_id','unique_code','status','is_deleted'];
}
