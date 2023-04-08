<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Control_tokens extends Model
{
    protected $table      = 'control_tokens';
    protected $primaryKey = 'id';    
    protected $softDelete = true;
    protected $fillable   = ['system', 'payload'];
    protected $dates      = ['created_at', 'updated_at', 'deleted_at']; 
}
