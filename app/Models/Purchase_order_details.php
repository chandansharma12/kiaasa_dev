<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Purchase_order_details extends Model
{
    protected $table = 'purchase_order_details';
    protected $fillable = ['po_id','invoice_no','invoice_date','vehicle_no','containers_count','images_list','comments','products_count','invoice_items','grn_id','user_id','debit_note_added','debit_note_no','credit_note_no','debit_note_data','fake_inventory','is_deleted'];
}
