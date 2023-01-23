<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Purchase_order_grn_qc_items extends Model
{
    protected $table = 'purchase_order_grn_qc_items';
    protected $fillable = ['grn_qc_id','inventory_id','quantity','qc_status','grn_qc_date','fake_inventory','is_deleted'];
}
