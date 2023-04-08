<?php

namespace App\Http\Controllers\Cms;

use Illuminate\Http\Request;
use App\Http\Controllers\CmsController;
use App\Model\Integration_queue;
use App\Model\Schedules;
use Validator;

class Integration_queueController extends CmsController
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
        $integration_queue = Integration_queue::orderBy("id", "DESC");;
        

        if($request->input('filter_search'))
        {           
           $integration_queue->where(function($query) use ($request) {
                $query->where('origin', 'like', '%'.$request->input('filter_search').'%')
               ->orWhere('destiny_key', 'like', '%'.$request->input('filter_search').'%')
               ->orWhere('origin_key', 'like', '%'.$request->input('filter_search').'%');
           });
        }

        if($request->input('filter_status'))
        {           
           $integration_queue->where('status', $request->input('filter_status'));
        }

        if($request->input('filter_start_date'))
        {           
           $integration_queue->where(function($query) use ($request) {
                $query->whereDate('created_at', '>=', $request->input('filter_start_date'))
                ->orWhereDate('updated_at', '>=', $request->input('filter_start_date'));
           });
        }

        if($request->input('filter_end_date'))
        {           
            $integration_queue->where(function($query) use ($request) {
                $query->whereDate('created_at', '>=', $request->input('filter_end_date'))
                ->orWhereDate('updated_at', '>=', $request->input('filter_end_date'));
           });
        }

        if($request->input('filter_schedule'))
        {           
            $integration_queue->where(function($query) use ($request) {
                $query->where('origin_command', $request->input('filter_schedule'))
                ->orWhere('destiny_command', $request->input('filter_schedule'));
           });
        }

        return view("cms/integration-queue/index", array(
        	"integration_queue" => $integration_queue->paginate(100),
            "schedule"          => Schedules::where('status', true)->orderBy('title', 'ASC')->pluck('title', 'command')
        ));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {   
    	$integration_queue = Integration_queue::find($id);
        if (empty($integration_queue)) {
           abort(404); 
        }

        return view("cms/integration-queue/show", array(
            "integration_queue"  => $integration_queue
        ));
    }

      /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request, $id)
    {   
        $integration_queue = Integration_queue::where('status', 3)->find($id);
        
        if (empty($integration_queue)) {
           abort(404); 
        }

        $integration_queue->delete();
        $request->session()->flash('alert', array('code'=> 'success', 'text'  => 'Operação realizada com sucesso!'));
        return redirect(route('cms-integration-queue', $this->queryFilters));
    }
	
	/**
     * Set to integration queue.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function setToQueue(Request $request, $id)
    {   
        $integration_queue = Integration_queue::where('status', 3)->find($id);
        
        if (empty($integration_queue)) {
           abort(404); 
        }

        $integration_queue->error_log = null; // Limpa o log
        $integration_queue->status = 2; // Coloca o registro na fila para ser integrado novamente 
		$integration_queue->save();
		
        $request->session()->flash('alert', array('code'=> 'success', 'text'  => 'Adicionado à fila de integração com sucesso!'));
        return redirect(route('cms-integration-queue', $this->queryFilters));
    }
}
