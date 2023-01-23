<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;

class Story_master extends Model
{
    protected $table = 'story_master';
    protected $fillable = ['name','design_count','production_design_count','story_year','status','is_deleted'];
}
