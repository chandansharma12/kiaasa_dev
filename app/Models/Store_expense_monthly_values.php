<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Store_expense_monthly_values extends Model
{
    protected $table = 'store_expense_monthy_values';
    protected $fillable = ['store_id','expense_date','expense_id','expense_value','user_id','is_deleted'];
}
