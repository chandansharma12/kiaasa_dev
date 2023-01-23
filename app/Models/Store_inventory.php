<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Store_inventory extends Model
{
    protected $table = 'store_inventory';
    protected $fillable = ['store_id','inv_date','opening_inv','sale_inv','wh_to_store_inv','store_to_wh_inv','store_to_wh_cancel_inv','store_to_wh_comp_inv','store_transfer_rec_inv','store_transfer_push_inv','store_transfer_push_cancel_inv','closing_inv','is_deleted'];
}
