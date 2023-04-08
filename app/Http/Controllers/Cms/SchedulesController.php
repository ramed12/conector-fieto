<?php

namespace App\Http\Controllers\Cms;

use Illuminate\Http\Request;
use App\Http\Controllers\CmsController;
use App\Model\Schedules;
use Validator;

class SchedulesController extends CmsController
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
        $schedules = Schedules::orderBy("title", "ASC");;
        

        if($request->input('filter_search'))
        {           
           $schedules->where(function($query) use ($request) {
                $query->where('title', 'like', '%'.$request->input('filter_search').'%')
               ->orWhere('text', 'like', '%'.$request->input('filter_search').'%');
           });
        }

        if($request->input('filter_status'))
        {           
           $schedules->where('status', $request->input('filter_status'));
        }

        return view("cms/schedules/index", array(
        	"schedules" => $schedules->paginate(50)
        ));
    }

      /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view("cms/schedules/show");
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {   
    	$schedules = Schedules::find($id);
        if (empty($schedules)) {
           abort(404); 
        }

        return view("cms/schedules/show", array(
            "schedules"        => $schedules
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
			'status'     => 'required'
        ]);

        $niceNames = array(
            'status'     => 'status'
        );

        $validator->setAttributeNames($niceNames); 

        if($validator->fails()) {
            return redirect(route('cms-schedules-show', [$id, $this->queryFilters]))->withErrors($validator->messages())->withInput();        
        } 

        try {
           
            $schedules = Schedules::find($id);
            $schedules->update((!empty($request->input('password'))) ? $request->except(["image", "title", "command", "text"]) : $request->except(["password", "image", "title", "command", "text"]));
            
            $request->session()->flash('alert', array('code'=> 'success', 'text'  => 'Operação realizada com sucesso!'));
        } catch (Exception $e) {
            $request->session()->flash('alert', array('code'=> 'danger', 'text'  => $e));
        }
       
        return redirect(route('cms-schedules', $this->queryFilters));
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
            $schedules = Schedules::find($id);

            if(empty($schedules)) {
                abort(404);
            }

            $schedules->delete();
            $request->session()->flash('alert', array('code'=> 'success', 'text'  => 'Operação realizada com sucesso!'));
        } catch (Exception $e) {
            $request->session()->flash('alert', array('code'=> 'danger', 'text'  => $e));
        }
       
        return redirect(route('cms-schedules', $this->queryFilters));
    }
}
