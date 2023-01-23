<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Store_products_demand_courier extends Model
{
    protected $table = 'store_products_demand_courier';
    protected $fillable = ['type','invoice_id','demand_id','courier_detail','vehicle_detail','boxes_count','transporter_name','transporter_gst','docket_no','eway_bill_no','lr_no','ship_to','dispatch_by','fake_inventory','demand_status','status','is_deleted'];
}
