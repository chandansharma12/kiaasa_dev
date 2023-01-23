<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Scheduled_tasks extends Model
{
    protected $table = 'scheduled_tasks';
    protected $fillable = ['task_type','task_ref_id','task_ref_no','task_items_count','task_items_comp_count','task_status','items_limit','cron_status','is_deleted'];
}
