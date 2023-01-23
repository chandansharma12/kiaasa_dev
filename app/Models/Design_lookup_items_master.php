<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Design_lookup_items_master extends Model
{
    protected $table = 'design_lookup_items_master';
    protected $fillable = ['name','type','pid','description','api_data','slug','status','is_deleted'];
}
