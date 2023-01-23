<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Production_size_counts extends Model
{
    protected $table = 'production_size_counts';
    protected $fillable = ['size','item_count','slug','status','is_deleted'];
}
