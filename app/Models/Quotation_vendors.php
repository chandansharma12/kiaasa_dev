<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Quotation_vendors extends Model
{
    protected $table = 'quotation_vendors';
    protected $fillable = ['quotation_id','vendor_id','quotation_submitted','submitted_on','status','is_deleted'];
}
