<?php

namespace App\Console\Commands\Orquestra;

use Illuminate\Console\Command;
use App\Model\Schedules;
use App\Model\Integration_queue;
use Illuminate\Support\Facades\Http;
use App\Traits\OrquestraApi;
use App\Traits\AlertMail;
use DB;
use Str;

class OrquestraDinamicsChargeMeasurementsQueue extends Command
{
    use OrquestraApi, AlertMail;

    /**
     * The name and signature of the console command.
     *
     * @var string
    */
    protected $signature = 'Integration:OrquestraDinamicsChargeMeasurementsQueue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotina para colocar os medições do Orquestra na fila de integração com o dynamics';

    /**
     * The console command variables custom.
     *
     * @var string
    */
    protected $origin_model    = '';
    protected $title           = 'Orquestra - Carga das Medições Para o Dynamics';
    protected $origin          = 'Orquestra - Medições';
    protected $origin_command  = null;
    protected $destiny_model   = null;
    protected $destiny         = 'Dynamics - Medições';
    protected $destiny_command = 'Integration:OrquestraDinamicsProcessingMeasurementsQueue';
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
        
        $response = Http::withToken($this->getAccessToken())
        ->withOptions([
            "verify" => false
        ])
        ->acceptJson()
        ->get(env('WS_ORQUESTRA_ENDPOINT')."/PropostaItemExecucao/ObterMedicoes");

        if (!$response->ok()) {
            $error = 'Erro ao executar a schedule '.$this->title.'.';
            $this->sendAlert($schedule->email, $error, $this->title);
            return $this->info($error);
        }        

        $measurements = $response->json();
        $totalRows    = 0; 

        foreach ($measurements['data']  as $key => $value) {

            $data = [
                "CND_FILIAL" => $value["filial"],
                "CND_CONTRA" => $value["nrContrato"],
                "CND_COMPET" => $value["competencia"],
                "CND_REVISA" => "01",
                "CND_CONDPG" => $value["condPagto"]              
            ];

            $data['DADOSMEDICAO'][] = [
                "CNE_FILIAL" => $value["filial"],
                "CNE_ITEM"   => $value["numeroItem"],
                "CNE_QUANT"  => $value["quantidade"],
                "CNE_VLUNIT" => "",
                "CNE_CC"     => $value["uo"],
                "CNE_CONTA"  => $value["ctaContabil"],
                "CNE_ITEMCT" => $value["cr"]
            ];
            
            $integration_queue = Integration_queue::create(array(
                'origin_model'    => $this->origin_model,
                'origin_key'      => $value['id'],
                'origin'          => $this->origin,
                'origin_command'  => $this->signature,
                'destiny_model'   => $this->destiny_model,
                'destiny_key'     => $value['id'],
                'destiny'         => $this->destiny,
                'destiny_command' => $this->destiny_command,
                'properties'      => base64_encode(json_encode($data)),
                'status'          => 2
            ));

            $totalRows++;  
        }

        return $this->info('A Rotina '.$this->title.' foi executada com sucesso, foi colocado na fila  '.$totalRows.' registros para integração.');      
    }
}
