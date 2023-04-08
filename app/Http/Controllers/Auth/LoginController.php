<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthCustomController;
use Auth;
use Validator;

class LoginController extends AuthCustomController
{

    use AuthenticatesUsers;
    
    /**
     * Create a new controller instance.
     *
     * @return void
    */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function index(Request $request) {

        return view("auth/login/index");
    }

    public function authenticate(Request $request) {

        $validator = Validator::make($request->input(), [
            'email'    => 'required',
            'password' => 'required'
        ]);

        $niceNames = array(
            'email'    => 'email',
            'password' => 'senha'
        );

        $validator->setAttributeNames($niceNames); 

        if($validator->fails()) {
            return redirect(route('auth'))->withErrors($validator->messages())->withInput();        
        }

        if(Auth::guard("cms")->attempt(['email' => $request->input('email'), 'password' => $request->input('password'), 'status' => 1], true)) {
            return redirect(route('cms-home'));
        }        

        $request->session()->flash('alert', array('code'=> 'danger', 'text'  => 'Acesso não autorizado!'));

        return redirect(route("auth"))->withInput();
    }

    public function logout(Request $request) {
        Auth::guard("cms")->logout();       
        $request->session()->flash('alert', array('code'=> 'success', 'text'  => 'Você foi desconectado!'));
        return redirect(route('auth'));
    }
}
