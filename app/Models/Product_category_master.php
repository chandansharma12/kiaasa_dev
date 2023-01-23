<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Product_category_master extends Model
{
    protected $table = 'product_category_master';
    protected $fillable = ['name','product_style','parent_id','type_id','status','is_deleted'];
}
