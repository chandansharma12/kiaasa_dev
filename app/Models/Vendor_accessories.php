<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Vendor_accessories extends Model
{
    protected $table = 'vendor_accessories';
    protected $fillable = ['vendor_id','accessory_id','po_id','quantity','date_provided','is_deleted'];
}
