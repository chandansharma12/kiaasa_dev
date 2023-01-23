<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Category_hsn_code extends Model
{
    protected $table = 'category_hsn_code';
    protected $fillable = ['category_id','hsn_code','is_deleted'];
}
