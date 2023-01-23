<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class store_products_demand extends Model
{
    protected $table = 'store_products_demand';
    protected $fillable = ['store_id','invoice_no','credit_invoice_no','user_id','approver_id','demand_status','comments','courier_id','demand_type','push_demand_id','from_store_id','discount_applicable','discount_percent','gst_inclusive','store_data','store_state_id','from_store_data','crn_user_id','cancel_user_id','cancel_comments','cancel_date','receive_docket_no','receive_date','debit_note_added','debit_note_no','credit_note_no','debit_note_data','inv_type','total_data','total_data_hsn','total_data_hsn_1','transfer_field','transfer_percent','invoice_type','demand_edited','debit_note_date','credit_note_date','fake_inventory','transfer_return_demand','company_gst_no','company_gst_name','status','is_deleted'];
}
