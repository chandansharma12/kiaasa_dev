<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $table = 'store';
    protected $fillable = ['store_name','region_id','address','address_line1','address_line2','city_name','postal_code','state_id','phone_no','gst_no','gst_name','store_code','store_id_code','gst_applicable','gst_type','store_type','api_access_key','zone_id','bags_inventory','store_info_type','google_name','display_name','front_picture','back_picture','latitude','longitude','ecommerce_status','slug','status','is_deleted'];
}
