<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class From_to extends Model
{
    use SoftDeletes;

    protected $table      = 'from_to';
    protected $primaryKey = 'id';    
    protected $softDelete = true;
    protected $fillable   = ['filial','command','field','value_origin','value_destiny','text'];
    protected $dates      = ['created_at', 'updated_at', 'deleted_at']; 


    public function schedules()
    {
        return $this->hasOne('App\Model\Schedules', 'command', 'command');
    }

}