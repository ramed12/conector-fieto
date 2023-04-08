<?php

namespace App\Http\Controllers\Cms;

use Illuminate\Http\Request;
use App\Http\Controllers\CmsController;
use App\Model\Users;
use Auth;
use Validator;

class MY_dataController extends CmsController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view("cms/my-data/index", array(
        	"users" => Users::find(Auth::guard("cms")->user()->id)
        ));
    }

     /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
    	$validator = Validator::make($request->input(), [
           'first_name' => 'required',
           'last_name'  => 'required',
           'email'      => 'required'
        ]);

        $niceNames = array(
           'first_name'=> 'nome',
           'last_name'  => 'sobrenome',
           'email'      => 'email'
        );

        $validator->setAttributeNames($niceNames); 

        if($validator->fails()) {
            return redirect(route('cms-my-data'))->withErrors($validator->messages())->withInput();        
        }

        $users = Users::find(Auth::guard("cms")->user()->id);

        if (empty($users)) {
        	abort(404);
        }

        try {

        	$users->update((!empty($request->input('password'))) ? $request->except(["image"]) : $request->except(["password", "image"]));
            Auth::guard('cms')->login($users);        	
        	$request->session()->flash('alert', array('code'=> 'success', 'text'  => "Dados alterados com sucesso!"));
        } catch (Exception $e) {
        	$request->session()->flash('alert', array('code'=> 'danger', 'text'  => "Não conseguimos alterar sua senha!"));
        }  

        return redirect(route('cms-my-data'));      
    }
}
