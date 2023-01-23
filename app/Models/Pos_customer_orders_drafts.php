<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Pos_customer_orders_drafts extends Model
{
    protected $table = 'pos_customer_orders_drafts';
    protected $fillable = ['store_id','products_count','product_barcodes','staff_data','is_deleted'];
}
