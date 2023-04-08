<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\ResetsPasswords;
use App\Http\Controllers\AuthCustomController;
use App\Model\Users;
use App\Model\Password_resets;
use Validator;
use Auth;
use Password;
use Hash;
use DB;

class ResetPasswordController extends AuthCustomController
{
    use ResetsPasswords;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function index(Request $request) {

        $token = $request->input('token');
        $data  = json_decode(base64_decode($request->input('hash'))); 

        if(!empty($data)) {
            return redirect(route('auth'));
        }    


        return view("auth/reset-password/index", array(
            'user'  => $data->user,
            'email' => $data->email,
            'id'    => $data->id,
            'token' => $token
        ));
    }


    public function update(Request $request) {

        $validator = Validator::make($request->input(), [
            'password'              => 'required',
            'password_confirmation' => 'required'
        ]);

        $niceNames = array(
            'password'              => 'senha',
            'password_confirmation' => 'confirmar senha'
        );

        $validator->setAttributeNames($niceNames); 

        if($validator->fails()) {
            return redirect(route('auth-reset-password', http_build_query($request->except(["password", "_token", "password_confirmation", "user", "email", "id"]))))->withErrors($validator->messages())->withInput();        
        } 


        if($request->input('password') != $request->input('password_confirmation')) {
            $request->session()->flash('alert', array('code'=> 'danger', 'text'  => "Senhas não confere!"));
            return redirect(route('auth-reset-password', http_build_query($request->except(["password", "_token", "password_confirmation", "user", "email", "id"]))));  
        } 

        if(strlen($request->input('password')) < 6) {
            $request->session()->flash('alert', array('code'=> 'danger', 'text'  => "Senhas senha deve conter no minimo 6 caracteres!"));
            return redirect(route('auth-reset-password', http_build_query($request->except(["password", "_token", "password_confirmation", "user", "email", "id"]))));  
        }  

        $password_resets = Password_resets::find($request->input('email'));   

        if(!empty($password_resets)) {           
            
            if(!Hash::check($request->input('token'), $password_resets->token)) {
                $request->session()->flash('alert', array('code'=> 'danger', 'text'  => "Token Invalido!"));
                return redirect(route('auth-reset-password', http_build_query($request->except(["password", "_token", "password_confirmation", "user", "email", "id"]))));  
            }
        }

        $users = Users::find($request->input('id'));

        if(empty($users)){
            $request->session()->flash('alert', array('code'=> 'danger', 'text'  => "Não foi possível alterar sua senha!"));
            return redirect(route('auth-reset-password'));          
        }

        $users->password = $request->input('password');
        $users->save();
        $password_resets->delete();

        Auth::guard('cms')->login($users);
        $request->session()->flash('alert', array('code'=> 'success', 'text'  => 'Senha alterada com sucesso!'));
        return redirect(route('cms-home'));
        
        $request->session()->flash('alert', array('code'=> 'danger', 'text'  => "Não conseguimos alterar sua senha!"));
        return redirect(route('auth-reset-password', http_build_query($request->except(["password", "_token", "password_confirmation", "user", "email", "id"]))));     
    }
}
