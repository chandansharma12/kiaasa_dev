<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Pos_customer_orders_errors extends Model
{
    protected $table = 'pos_customer_orders_errors';
    protected $fillable = ['store_id','total_items','error_text','inv_ids','client_price_list','server_price_list','client_total_price','server_total_price','cash_amount','card_amount','ewallet_amount','voucher_amount','is_deleted'];
}
