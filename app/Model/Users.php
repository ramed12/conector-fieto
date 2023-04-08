<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Mail;

class Users extends Authenticatable
{
    use SoftDeletes;
    use Notifiable;
    use HasApiTokens;

    protected $table      = 'users';
    protected $primaryKey = 'id';    
    protected $softDelete = true;
    protected $fillable   = ['first_name', 'last_name', 'email', 'email_verified_at', 'password', 'status'];
    protected $dates      = ['created_at', 'updated_at', 'deleted_at']; 
    protected $hidden     = ['password', 'remember_token'];
    protected $casts      = [ 
        'email_verified_at' => 'datetime',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
        'deleted_at'        => 'datetime'
    ];

    public function setPasswordAttribute($password)
    {   
        $this->attributes['password'] = bcrypt($password);
    }

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

    public function sendPasswordResetNotification($token)
    {
        $data = $this;
        
        return Mail::send('auth/emails/i-forgot-my-password', ['data' => $data, "token" => $token], function ($email) use ($data) {
            $email->from(env('GAOCONNECTOR_EMAIL'), env('GAOCONNECTOR_NAME'));
            $email->to($data->email, $data->name)->subject("GAO Connector - Redefinição de Senha");
        });
    }
}
