<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Vendor_quotation extends Model
{
    protected $table = 'vendor_quotation';
    protected $fillable = ['design_quotation_id','design_id','design_type','design_type_row_id','price','comment'];
}
