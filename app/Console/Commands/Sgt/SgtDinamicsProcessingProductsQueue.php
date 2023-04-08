<?php

namespace App\Console\Commands\Sgt;

use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use App\Model\Schedules;
use App\Model\Integration_queue;
use App\Model\From_to;
use App\Traits\DynamicsApi;
use App\Traits\AlertMail;
use Str;

class SgtDinamicsProcessingProductsQueue extends Command
{
    use DynamicsApi, AlertMail;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Integration:SgtDinamicsProcessingProductsQueue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotina para processar os produtos do Sgt na fila de integração com o dynamics';

    /**
     * The console command variables custom.
     *
     * @var string
    */
    protected $origin_model    = '';
    protected $title           = 'SGT - Processamento dos Produtos Para o Dynamics';
    protected $origin          = 'SGT - Produtos';
    protected $origin_command  = null;
    protected $destiny_model   = null;
    protected $destiny         = '';
    protected $destiny_command = '';
    protected $pagination      = 1;

    /**
     * Create a new command instance.
     *
     * @return void
    */
    public function __construct()
    {
        parent::__construct();
        $this->origin_command = $this->signature;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {        
        $schedule = Schedules::where('command', $this->signature)
        ->first();

        if(empty($schedule)) {
            
            $schedule = Schedules::create(array(
                'title'      => $this->title,
                'text'       => $this->description,
                'command'    => $this->signature,
                'pagination' => $this->pagination,
                'status'     => true
            ));
        }

        if($schedule->status != 1) {
            return $this->info('O Schedule '.$this->title.' está inativo.');
        }
       
        $data          = Integration_queue::where('destiny_command', $this->signature)->take($schedule->pagination)->where('status', 2)->get();
        $dataIds       = $data->pluck('id');
        $totalRows     = 0;
        $totalRowsErro = 0;

        Integration_queue::whereIn('id', $dataIds)->update([
            'status' => '1'
        ]);

        foreach ($data as $key => $value) {

            $properties = (array) json_decode(base64_decode($value->properties));          

            try {

                $response = Http::withOptions([
                    "verify" => false
                ])
                ->post(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_PRODUCTS_STORE'), [
                    "B1_COD"     => (string) $properties["B1_COD"],
                    "B1_DESC"    => (string) $properties["B1_DESC"],
                    "B1_TIPO"    => (string) $properties["B1_TIPO"],
                    "B1_UM"      => (string) $properties["B1_UM"],
                    "B1_LOCPAD"  => (string) $properties["B1_LOCPAD"],
                    "B1_CC"      => (string) $properties["B1_CC"],   
                    "B1_PCDN"    => (string) $properties["B1_PCDN"],              
                    "B1_XCODORI" => (string) $properties["B1_XCODORI"],
                    "B1_XSISORI" => (string) $properties["B1_XSISORI"]
                ]);

                if($response->successful()) {
                    $value->status = 1;
                    $value->save();
                    $totalRows++;
                    unset($dataIds[$key]);
                } else {                   

                    $value->status    = 3;
                    $value->error_log = base64_encode($response->body());
                    $value->save();

                    unset($dataIds[$key]);

                    $totalRowsErro++;

                    $this->sendAlert($schedule->email, $response->body(), $this->title);
                }
                
            } catch (\Illuminate\Http\Client\Exception | \Illuminate\Http\Client\ConnectionException | \Illuminate\Database\QueryException | \Illuminate\Http\Client\RequestException | \Illuminate\Http\Client\ClientException | \Illuminate\Http\Client\ServerException | \Illuminate\Http\Client\BadResponseException | \Exception $e) {
                
                Integration_queue::whereIn('id', $dataIds)->update([
                    'status' => '2'
                ]);

                return $this->info($e->getMessage());
            }
        }

        return $this->info('A Rotina '.$this->title.' foi executada com sucesso, foi integrado  '.$totalRows.' registros e '.$totalRowsErro.' falhou.');      
    }
}