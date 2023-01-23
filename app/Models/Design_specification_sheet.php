<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Design_specification_sheet extends Model
{
    protected $table = 'design_specification_sheet';
    protected $fillable = ['specification_id','design_id','role_id','size_s','size_m','size_l','size_xl','size_xxl','size_xxxl','allowlance','status','is_deleted'];
}
