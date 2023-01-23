<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Purchase_order extends Model
{
    protected $table = 'purchase_order';
    protected $fillable = ['quotation_id','user_id','type_id','vendor_id','other_cost','other_comments','gst_type','delivery_date','order_no','qc_status','qc_date','qc_comments','category_id','static_po','fake_inventory','company_data','process_start','process_end','is_deleted'];
}
