<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class PosProductWishlist extends Model
{
    protected $table = 'pos_product_wishlist';
    protected $fillable = ['product_id','user_id','created_at','updated_at'];
}
