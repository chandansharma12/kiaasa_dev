<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Debit_notes extends Model
{
    protected $table = 'debit_notes';
    protected $fillable = ['po_id','invoice_id','debit_note_no','credit_note_no','debit_note_type','items_count','comments','debit_note_status','cancel_user_id','cancel_date','cancel_comments','is_deleted'];
}
