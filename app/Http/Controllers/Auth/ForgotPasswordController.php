<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthCustomController;
use Password;
use Validator;

class ForgotPasswordController extends AuthCustomController
{
    use SendsPasswordResetEmails;

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

        return view("auth/forgot-password/index");
    }


    public function update(Request $request) {

        $validator = Validator::make($request->input(), [
            'email'     => 'required'
        ]);

        $niceNames = array(
            'email'     => 'email'
        );

        $validator->setAttributeNames($niceNames); 

        if($validator->fails()) {
            return redirect(route('auth-i-forgot-my-password'))->withErrors($validator->messages())->withInput();        
        }

        $response = Password::broker('cms')->sendResetLink(['email' => $request->input('email')]);

        if ($response == Password::RESET_LINK_SENT)
        {           
            $request->session()->flash('alert', array('code'=> 'success', 'text'  => "Enviamos um e-mail para ".$request->input('email')." com as instruções para você redefinir sua senha de acesso."));
            return redirect(route('auth-i-forgot-my-password'));   
        }          

        $request->session()->flash('alert', array('code'=> 'danger', 'text'  => 'Redefinição Não Autorizado!'));

        return redirect(route("auth-i-forgot-my-password"))->withInput();       
    }
}
