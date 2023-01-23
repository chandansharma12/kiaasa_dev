<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Accessories extends Model
{
    protected $table = 'accessories';
    protected $fillable = ['accessory_name','description','rate','gst_percent','is_deleted'];
}
