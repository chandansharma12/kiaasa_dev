<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Debit_note_items extends Model
{
    protected $table = 'debit_note_items';
    protected $fillable = ['debit_note_id','item_id','item_cost','item_qty','item_invoice_cost','base_rate','gst_percent','gst_amount','base_price','invoice_base_rate','invoice_gst_percent','invoice_gst_amount','invoice_base_price','debit_note_status','is_deleted'];
}
