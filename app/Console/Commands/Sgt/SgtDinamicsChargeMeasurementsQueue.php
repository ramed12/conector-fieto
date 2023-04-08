<?php

namespace App\Console\Commands\Sgt;

use Illuminate\Console\Command;
use App\Model\Schedules;
use App\Model\SchedulesLog;
use App\Model\Integration_queue;
use Illuminate\Support\Facades\Http;
use App\Traits\DynamicsApi;
use App\Traits\AlertMail;
use DB;
use Str;
use DateTime;

class SgtDinamicsChargeMeasurementsQueue extends Command
{
    use DynamicsApi, AlertMail;

    /**
     * The name and signature of the console command.
     *
     * @var string
    */
    protected $signature = 'Integration:SgtDinamicsChargeMeasurementsQueue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotina para colocar as medições do Sgt na fila de integração com o dynamics';

    /**
     * The console command variables custom.
     *
     * @var string
    */
    protected $origin_model    = '';
    protected $title           = 'SGT - Carga das Medições Para o Dynamics';
    protected $origin          = 'SGT - Medições';
    protected $origin_command  = null;
    protected $destiny_model   = null;
    protected $destiny         = 'Dynamics - Medições';
    protected $destiny_command = 'Integration:SgtDinamicsProcessingMeasurementsQueue';
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

        $curretDateTime = new DateTime();
        $endDate        = $curretDateTime->format('Y-m-d\TH:i:s');
		
		$lastExecSchedule = SchedulesLog::where('id_schedule', $schedule->id)
            ->first();
		
		if(empty($lastExecSchedule)) {
			$lastExecSchedule = SchedulesLog::create(array(
                'id_schedule' => $schedule->id,
                'last_exec'   => $endDate,
				'command' => $schedule->command
            ));
		}
		
		$startDate = $lastExecSchedule->last_exec;

        $response = Http::withToken(env('WS_SGT_API_TOKEN'))
        ->withOptions([
            "verify" => false
        ])
        ->acceptJson()
        ->get(env('WS_SGT_API_ENDPOINT')."/producoes?deDataModificacao=".urlencode($startDate)."&ateDataModificacao=".urlencode($endDate));

        if (!$response->ok()) {
            $error = 'Erro ao executar a schedule '.$this->title.'.';
            $this->sendAlert($schedule->email, $error, $this->title);
            return $this->info($error);
        }        

        $measurements = $response->json();
        $totalRows    = 0; 

        if (!empty($measurements['Producoes'])) {   

            if (isset($measurements['Producoes']['Producao']['codigoUnidade'])) {


                $responseContract = Http::withToken(env('WS_SGT_API_TOKEN'))
                ->withOptions([
                    "verify" => false
                ])
                ->acceptJson()
                ->get(env('WS_SGT_API_ENDPOINT')."/atendimentos?idAtendimento=".$measurements['Producoes']['Producao']['idAtendimento']);

                if(!$responseContract->ok()) {
                    $error = 'Erro ao executar a schedule '.$this->title.' para buscar a filial da medição.';
                    $this->sendAlert($schedule->email, $error, $this->title);
                    return $this->info($error);
                }  

                $responseContract = $responseContract->json();

                if (!isset($responseContract['Atendimentos']['Atendimento'])) {
                    $error = 'Erro ao executar a schedule '.$this->title.' para buscar a filial da medição.';
                    $this->sendAlert($schedule->email, $error, $this->title);
                    return $this->info($error);
                }

                $data = [
                    "CND_NUMMED" => $measurements['Producoes']['Producao']["idProducao"],
                    "CND_FILIAL" => $responseContract['Atendimentos']['Atendimento']['unidade'],
                    "CND_CONTRA" => "SGT-".$measurements['Producoes']['Producao']["idProposta"],
                    "CND_COMPET" => date('m/Y', strtotime($measurements['Producoes']['Producao']["dataApropriacao"])),
                    "CND_REVISA" => "01",
                    "CND_CONDPG" => ""              
                ];

                $data['DADOSMEDICAO'][] = [
                    "CNE_FILIAL" => $responseContract['Atendimentos']['Atendimento']['unidade'],
                    "CNE_ITEM"   => "01",
                    "CNE_QUANT"  => $measurements['Producoes']['Producao']["qtdeHorasEnsaiosCalibracoes"],
                    "CNE_VLUNIT" => "",
                    "CNE_CC"     => "",
                    "CNE_CONTA"  => "",
                    "CNE_ITEMCT" => "",
                    "CNE_PRODUT" => $measurements['Producoes']['Producao']['idProdutoRegional']
                ];
                
                $integration_queue = Integration_queue::create(array(
                    'origin_model'    => $this->origin_model,
                    'origin_key'      => $measurements['Producoes']['Producao']['idProposta'],
                    'origin'          => $this->origin,
                    'origin_command'  => $this->signature,
                    'destiny_model'   => $this->destiny_model,
                    'destiny_key'     => $measurements['Producoes']['Producao']['idProposta'],
                    'destiny'         => $this->destiny,
                    'destiny_command' => $this->destiny_command,
                    'properties'      => base64_encode(json_encode($data)),
                    'status'          => 2
                ));

                $totalRows++; 
             } else {

                foreach ($measurements['Producoes']['Producao']  as $key => $value) {



                    $responseContract = Http::withToken(env('WS_SGT_API_TOKEN'))
                    ->withOptions([
                        "verify" => false
                    ])
                    ->acceptJson()
                    ->get(env('WS_SGT_API_ENDPOINT')."/atendimentos?idAtendimento=".$value['idAtendimento']);

                    if(!$responseContract->ok()) {
                        $error = 'Erro ao executar a schedule '.$this->title.' para buscar a filial da medição.';
                        $this->sendAlert($schedule->email, $error, $this->title);
                        return $this->info($error);
                    }  

                    $responseContract = $responseContract->json();

                    if (!isset($responseContract['Atendimentos']['Atendimento'])) {
                         $error = 'Erro ao executar a schedule '.$this->title.' para buscar a filial da medição.';
                        $this->sendAlert($schedule->email, $error, $this->title);
                        return $this->info($error);
                    }

                    $data = [
                        "CND_NUMMED" => $value["idProducao"],
                        "CND_FILIAL" => $responseContract['Atendimentos']['Atendimento']['unidade'],
                        "CND_CONTRA" => "SGT-".$value["idProposta"],
                        "CND_COMPET" => date('m/Y', strtotime($value["dataApropriacao"])),
                        "CND_REVISA" => "01",
                        "CND_CONDPG" => ""              
                    ];

                    $data['DADOSMEDICAO'][] = [
                        "CNE_FILIAL" => $responseContract['Atendimentos']['Atendimento']['unidade'],
                        "CNE_ITEM"   => "01",
                        "CNE_QUANT"  => $value["qtdeHorasEnsaiosCalibracoes"],
                        "CNE_VLUNIT" => "",
                        "CNE_CC"     => "",
                        "CNE_CONTA"  => "",
                        "CNE_ITEMCT" => "",
                        "CNE_PRODUT" => $value['idProdutoRegional']
                    ];
                    
                    $integration_queue = Integration_queue::create(array(
                        'origin_model'    => $this->origin_model,
                        'origin_key'      => $value['idProposta'],
                        'origin'          => $this->origin,
                        'origin_command'  => $this->signature,
                        'destiny_model'   => $this->destiny_model,
                        'destiny_key'     => $value['idProposta'],
                        'destiny'         => $this->destiny,
                        'destiny_command' => $this->destiny_command,
                        'properties'      => base64_encode(json_encode($data)),
                        'status'          => 2
                    ));

                    $totalRows++;  
                } 
            }       
        }
		
		$lastExecSchedule->last_exec = $endDate;
		$lastExecSchedule->save();

        return $this->info('A Rotina '.$this->title.' foi executada com sucesso, foi colocado na fila  '.$totalRows.' registros para integração.');      
    }
}