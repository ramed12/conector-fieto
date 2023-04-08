<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBillsToReceive;
use App\Model\Integration_queue;

class BillsToReceiveController extends Controller
{
    protected $queryFilters;

    public function __construct(Request $request)
    {
        parent::__construct($request);

        $this->queryFilters = http_build_query($request->query());    
    }

    public function store(StoreBillsToReceive $request)
    {
        return Integration_queue::create(array(
            'origin_model'    => null,
            'origin_key'      => $request->input('E1_NUM'),
            'origin'          => 'Prothues - Contas a Receber',
            'origin_command'  => 'Integration:ProtheusDynamicsChargeBillsToReceiveQueue',
            'destiny_model'   => null,
            'destiny_key'     => $request->input('E1_NUM'),
            'destiny'         => 'Dynamics - Contas a Receber',
            'destiny_command' => 'Integration:ProtheusDynamicsProcessingBillsToReceiveQueue',
            'properties'      => base64_encode(json_encode($request->input())),
            'status'          => 2
        ));
    }
}