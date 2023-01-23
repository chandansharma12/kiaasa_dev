<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Design_quotation extends Model
{
    protected $table = 'design_quotation';
    protected $fillable = ['purchaser_id','design_id','vendor_id','message','quotation_status'];
}
