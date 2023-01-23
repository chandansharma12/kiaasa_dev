<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Store_expense_master_values extends Model
{
    protected $table = 'store_expense_master_values';
    protected $fillable = ['store_id','expense_id','expense_value','is_deleted'];
}
