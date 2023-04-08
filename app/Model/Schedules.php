<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schedules extends Model
{
    use SoftDeletes;

    protected $table      = 'schedules';
    protected $primaryKey = 'id';    
    protected $softDelete = true;
    protected $fillable   = ['title','text','command','pagination', 'email', 'status'];
    protected $dates      = ['created_at', 'updated_at', 'deleted_at']; 

    public function status()
    {   
        switch ($this->status) {
            case '1':
                return 'Ativo';
            break;
            case '2':
                return 'Inativo';
            break;
        }
    }
}
