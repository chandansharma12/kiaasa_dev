<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    protected $table = 'quotation';
    protected $fillable = ['mail_body','created_by','type_id','po_id','status','is_deleted'];
}
