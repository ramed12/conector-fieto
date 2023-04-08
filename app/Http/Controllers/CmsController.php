<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Model\Schedules;
use View;

class CmsController extends Controller
{
    public function __construct(){

    	View::share(array(
            "schedules_menu" => Schedules::where('status', true)->orderBy('title', 'ASC')->get()
        )); 
    }
}
