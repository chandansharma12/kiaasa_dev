<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Pos_product_images extends Model
{
    protected $table = 'pos_product_images';
    protected $fillable = ['product_id','image_name','image_title','image_type','status','is_deleted'];
}
