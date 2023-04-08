<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cities extends Model
{
	use SoftDeletes;

    protected $table      = "cities";
	protected $primaryKey = 'cities_id';
	protected $fillable   = ['name', 'status', 'states_id', 'cd_ibge', 'start_cep', 'end_cep', 'created_at', 'updated_at'];
	protected $dates      = ['created_at', 'updated_at', 'deleted_at']; 
}
