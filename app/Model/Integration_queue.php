<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Integration_queue extends Model
{
    use SoftDeletes, Notifiable;

    protected $table      = 'integration_queue';
    protected $primaryKey = 'id';    
    protected $softDelete = true;
    protected $fillable   = ['origin_model', 'origin_key', 'origin', 'origin_command', 'destiny_model', 'destiny_key', 'destiny', 'destiny_command', 'properties', 'error_log', 'status'];
    protected $dates      = ['created_at', 'updated_at', 'deleted_at']; 

    public function status()
    {   
        switch ($this->status) {
            case '1':
                return 'Integrado';
            break;
            case '2':
                return 'Não Integrado';
            break;
            case '3':
                return 'Falha No Processamento';
            break;
        }
    }
}
