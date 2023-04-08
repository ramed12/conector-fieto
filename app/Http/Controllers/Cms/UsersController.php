<?php

namespace App\Http\Controllers\Cms;

use Illuminate\Http\Request;
use App\Http\Controllers\CmsController;
use App\Model\Users;
use Validator;

class UsersController extends CmsController
{
   protected $queryFilters;

    public function __construct(Request $request)
    {
        parent::__construct($request);

        $this->queryFilters = http_build_query($request->query());    
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {   
        $users = Users::orderBy("id", "DESC");;
        

        if($request->input('filter_search'))
        {           
           $users->where(function($query) use ($request) {
                $query->where('first_name', 'like', '%'.$request->input('filter_search').'%')
               ->orWhere('last_name', 'like', '%'.$request->input('filter_search').'%')
               ->orWhere('email', 'like', '%'.$request->input('filter_search').'%');
           });
        }

        if($request->input('filter_status'))
        {           
           $users->where('status', $request->input('filter_status'));
        }

        return view("cms/users/index", array(
        	"users" => $users->paginate(50)
        ));
    }

      /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view("cms/users/show");
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator   = Validator::make($request->input(), [
        'first_name' => 'required',
        'last_name'  => 'required',
        'email'      => 'required|unique:users|email',
        'password'   => 'required',
        'status'     => 'required'
        ]);
        
        $niceNames   = array(
        'first_name' => 'nome',
        'last_name'  => 'sobrenome',
        'email'      => 'email',
        'password'   => 'senha',
        'status'     => 'status'
        );
        
        $validator->setAttributeNames($niceNames); 
        
        if($validator->fails()) {
        return redirect(route('cms-users-create', $this->queryFilters))->withErrors($validator->messages())->withInput();        
        } 

        try {
            $user = Users::create($request->all());        
            
            return redirect(route('cms-users-show', [$user->id, $this->queryFilters])); 
        } catch (Exception $e) {
            $request->session()->flash('alert', array('code'=> 'danger', 'text'  => $e));
            return redirect(route('cms-users', $this->queryFilters)); 
        }        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {   
    	$users = Users::find($id);
        if (empty($users)) {
           abort(404); 
        }

        return view("cms/users/show", array(
            "users"        => $users
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
         $validator = Validator::make($request->input(), [
			'first_name' => 'required',
			'last_name'  => 'required',
			'email'      => 'required|email',
			'status'     => 'required'
        ]);

        $niceNames = array(
            'first_name' => 'nome',
            'last_name'  => 'sobrenome',
            'email'      => 'email',
            'status'     => 'status'
        );

        $validator->setAttributeNames($niceNames); 

        if($validator->fails()) {
            return redirect(route('cms-users-show', [$id, $this->queryFilters]))->withErrors($validator->messages())->withInput();        
        } 

        try {
           
            $users = Users::find($id);
            $users->update((!empty($request->input('password'))) ? $request->except(["image"]) : $request->except(["password", "image"]));
            
            $request->session()->flash('alert', array('code'=> 'success', 'text'  => 'Operação realizada com sucesso!'));
        } catch (Exception $e) {
            $request->session()->flash('alert', array('code'=> 'danger', 'text'  => $e));
        }
       
        return redirect(route('cms-users', $this->queryFilters));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        try {
            $users = Users::find($id);

            if(empty($users)) {
                abort(404);
            }

            $users->delete();
            $request->session()->flash('alert', array('code'=> 'success', 'text'  => 'Operação realizada com sucesso!'));
        } catch (Exception $e) {
            $request->session()->flash('alert', array('code'=> 'danger', 'text'  => $e));
        }
       
        return redirect(route('cms-users', $this->queryFilters));
    }
}
