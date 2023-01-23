<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Gst_rates extends Model
{
    protected $table = 'gst_rates';
    protected $fillable = ['hsn_code','rate_percent','from_date','to_date','min_amount','max_amount','is_deleted'];
}
