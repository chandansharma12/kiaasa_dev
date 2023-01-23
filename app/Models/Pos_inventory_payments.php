<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Pos_inventory_payments extends Model
{
    protected $table = 'pos_inventory_payments';
    protected $fillable = ['vendor_id','start_date','end_date','inventory_count','inventory_net_price','inventory_cost_price','comment','user_id','is_deleted'];
}
