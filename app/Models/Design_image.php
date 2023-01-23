<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Design_image extends Model
{
    protected $table = 'design_images';
    
    protected $fillable = ['design_id','image_path','image_title','image_name','image_type','image_status'];
}
