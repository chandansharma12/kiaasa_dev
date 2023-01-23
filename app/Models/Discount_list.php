<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Discount_list extends Model
{
    protected $table = 'discount_list';
    protected $fillable = ['buy_items','get_items','gst_type','item_type','discount','is_deleted'];
}
