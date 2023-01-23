<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class User_profile extends Model
{
    protected $table = 'user_profile';
    protected $fillable = ['user_id','profile_picture','employee_id','gender','marital_status','dob','blood_group','address','city','state_id','postal_code','mobile_no','home_phone_no',
    'personal_email','emergency_contact_name','emergency_contact_relation','emergency_contact_phone_no','job_title','employment_type','employment_status','joining_date','releiving_date',
    'qualification_details','experience_details','supervisor_id','overtime_hourly_rate','annual_ctc','monthly_salary','is_deleted'];
}
