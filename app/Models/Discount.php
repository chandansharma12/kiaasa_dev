<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $table = 'discount_master';
    protected $fillable = ['category_id','store_id','sku','from_date','to_date','discount_type','from_price','to_price','buy_items','get_items','flat_price','discount_percent','season','gst_including','inv_type'];
    protected $dates = ['from_date', 'to_date'];
   
   protected $casts = [
    'from_date' => 'datetime', 'to_date' => 'datetime'
];
   
}
