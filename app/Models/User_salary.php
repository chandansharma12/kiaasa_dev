<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class User_salary extends Model
{
    protected $table = 'user_salary';
    protected $fillable = ['user_id','salary_month','salary_year','annual_ctc','monthly_salary','basic','da','hra','conveyance','medical','lta','overtime_hours','overtime_hourly_rate','overtime_wages','approved_leaves','unapproved_leaves','leaves_deduction','pf_deduction','net_salary','status','comments','is_deleted'];
}
