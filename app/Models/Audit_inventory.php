<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Audit_inventory extends Model
{
    protected $table = 'audit_inventory';
    protected $fillable = ['audit_id','inventory_id','store_id','product_status','scan_status','scan_date','present_system','present_store','present_warehouse','is_deleted'];
}
