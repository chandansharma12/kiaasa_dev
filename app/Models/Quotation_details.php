<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Quotation_details extends Model
{
    protected $table = 'quotation_details';
    protected $fillable = ['quotation_id','item_master_id','design_id','quantity','vendor_id','price','comments','design_id','status','is_deleted'];
}
