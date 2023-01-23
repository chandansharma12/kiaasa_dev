<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Pages_description extends Model
{
    protected $table = 'pages_description';
    protected $fillable = ['page_name','desc_type','desc_name','desc_detail','is_deleted'];
}
