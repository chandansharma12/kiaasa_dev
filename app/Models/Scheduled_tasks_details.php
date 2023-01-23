<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Scheduled_tasks_details extends Model
{
    protected $table = 'scheduled_tasks_details';
    protected $fillable = ['task_id','task_item_data','task_item_status','error_text','is_deleted'];
}
