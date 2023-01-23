<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Design_support_files extends Model
{
    protected $table = 'design_support_files';
    protected $fillable = ['user_id','design_id','file_name','display_name','file_number','file_status','is_deleted'];
}
