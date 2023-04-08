<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class SchedulesLog extends Model
{
    protected $table      = 'schedules_log';
    protected $primaryKey = 'id';
	public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_schedule',        
        'last_exec',
		'command',		
    ];
}
