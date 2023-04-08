<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Password_resets extends Model
{
	
	protected $table      = 'password_resets';   
	protected $primaryKey = 'email';
	protected $softDelete = true;
	protected $fillable   = ['email', 'token'];
	protected $dates      = ['created_at'];

    protected static $logAttributes = '*';
    protected static $logFillable   = true;
}
