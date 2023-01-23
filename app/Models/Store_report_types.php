<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Store_report_types extends Model
{
    protected $table = 'store_report_types';
    protected $fillable = ['store_id','report','report_type','is_deleted'];
}
