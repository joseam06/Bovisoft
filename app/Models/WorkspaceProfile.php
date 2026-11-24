<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkspaceProfile extends Model
{
    use SoftDeletes, \App\Models\Concerns\SyncsWorkspace;
    protected $fillable = ['user_id','name'];
}
