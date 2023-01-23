<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Design_review extends Model
{
    protected $table = 'design_reviews';
    
    protected $fillable = ['design_id','user_id','role_id','review_status','comment','is_deleted'];
}
