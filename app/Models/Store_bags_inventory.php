<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Store_bags_inventory extends Model
{
    protected $table = 'store_bags_inventory';
    protected $fillable = ['store_id','bags_assigned','date_assigned','is_deleted'];
}
