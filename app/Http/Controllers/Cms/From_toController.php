<?php

namespace App\Http\Controllers\Cms;

use Illuminate\Http\Request;
use App\Http\Controllers\CmsController;
use App\Model\From_to;
use App\Model\Schedules;
use Validator;

class From_toController extends CmsController
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
        $from_to = From_to::orderBy("id", "DESC");;
        

        if($request->input('filter_search'))
        {           
           $from_to->where(function($query) use ($request) {
                $query->where('field', 'like', '%'.$request->input('filter_search').'%')
               ->orWhere('text', 'like', '%'.$request->input('filter_search').'%');
           });
        }

        if($request->input('filter_schedule'))
        {           
            $from_to->where(function($query) use ($request) {
                $query->where('command', $request->input('filter_schedule'));
           });
        }

        return view("cms/from-to/index", array(
            "from_to"  => $from_to->paginate(50),
            "schedule" => Schedules::where('status', true)->orderBy('title', 'ASC')->pluck('title', 'command')
        ));
    }

      /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view("cms/from-to/show", [
            "schedule" => Schedules::where('status', true)->orderBy('title', 'ASC')->pluck('title', 'command')
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->input(), [
            'command'       => 'required',
            'field'         => 'required',
            'value_origin'  => 'required',
            'value_destiny' => 'required',
            'text'          => 'required'
        ]);

        $niceNames = array(
            'command'       => 'rotina',
            'filial'        => 'filial',
            'field'         => 'campo',
            'value_origin'  => 'valor de origem',
            'value_destiny' => 'valor de destino',
            'text'          => 'descrição'
        );

        $validator->setAttributeNames($niceNames); 

        if($validator->fails()) {
            return redirect(route('cms-from-to-create', $this->queryFilters))->withErrors($validator->messages())->withInput();        
        } 

        try {
            $user = From_to::create($request->all());        
            
            return redirect(route('cms-from-to-show', [$user->id, $this->queryFilters])); 
        } catch (Exception $e) {
            $request->session()->flash('alert', array('code'=> 'danger', 'text'  => $e));
            return redirect(route('cms-from-to', $this->queryFilters)); 
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
    	$from_to = From_to::find($id);
        if (empty($from_to)) {
           abort(404); 
        }

        return view("cms/from-to/show", array(
            "from_to"  => $from_to,
            "schedule" => Schedules::where('status', true)->orderBy('title', 'ASC')->pluck('title', 'command')
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
            'command'       => 'required',
            'field'         => 'required',
            'value_origin'  => 'required',
            'value_destiny' => 'required',
            'text'          => 'required'
        ]);

        $niceNames = array(
            'command'       => 'rotina',
            'filial'        => 'filial',
            'field'         => 'campo',
            'value_origin'  => 'valor de origem',
            'value_destiny' => 'valor de destino',
            'text'          => 'descrição'
        );

        $validator->setAttributeNames($niceNames); 

        if($validator->fails()) {
            return redirect(route('cms-from-to-show', [$id, $this->queryFilters]))->withErrors($validator->messages())->withInput();        
        } 

        try {
           
            $from_to = From_to::find($id);
            $from_to->update((!empty($request->input('password'))) ? $request->except(["image"]) : $request->except(["password", "image"]));
            
            $request->session()->flash('alert', array('code'=> 'success', 'text'  => 'Operação realizada com sucesso!'));
        } catch (Exception $e) {
            $request->session()->flash('alert', array('code'=> 'danger', 'text'  => $e));
        }
       
        return redirect(route('cms-from-to', $this->queryFilters));
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
            $from_to = From_to::find($id);

            if(empty($from_to)) {
                abort(404);
            }

            $from_to->delete();
            $request->session()->flash('alert', array('code'=> 'success', 'text'  => 'Operação realizada com sucesso!'));
        } catch (Exception $e) {
            $request->session()->flash('alert', array('code'=> 'danger', 'text'  => $e));
        }
       
        return redirect(route('cms-from-to', $this->queryFilters));
    }
}
