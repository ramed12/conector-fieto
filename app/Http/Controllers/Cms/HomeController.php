<?php

namespace App\Http\Controllers\Cms;

use Illuminate\Http\Request;
use App\Http\Controllers\CmsController;
use App\Model\Integration_queue;
use Auth;
use App\Model\Cities;

class HomeController extends CmsController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view("cms/home/index", array(
            "integration_queue" => Integration_queue::take(10)->orderBy('id', 'DESC')->get()
        ));
    }  
}
