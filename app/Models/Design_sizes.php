<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Design_sizes extends Model
{
    protected $table = 'design_sizes';
    protected $fillable = ['design_id','size_id','is_deleted'];
}
