<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Purchase_order_grn_qc extends Model
{
    protected $table = 'purchase_order_grn_qc';
    protected $fillable = ['grn_no','credit_note_no','po_id','type','comments','po_detail_id','other_data','grn_items','grn_edited','fake_inventory','status','is_deleted'];
}
