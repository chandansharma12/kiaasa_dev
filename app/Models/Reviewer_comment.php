<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Reviewer_comment extends Model
{
    protected $table = 'reviewer_comments';
    protected $fillable = ['design_id','reviewer_id','design_status','comment','version','review_type'];
}
