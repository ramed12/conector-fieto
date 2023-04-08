<?php

namespace App\Console\Commands\Dynamics;

use Illuminate\Console\Command;
use App\Model\Schedules;
use App\Model\Integration_queue;
use Illuminate\Support\Facades\Http;
use App\Traits\DynamicsApi;
use App\Traits\AlertMail;
use DB;
use Str;

class DynamicsProtheusChargeMeasurementsQueue extends Command
{
    use DynamicsApi, AlertMail;

    /**
     * The name and signature of the console command.
     *
     * @var string
    */
    protected $signature = 'Integration:DynamicsProtheusChargeMeasurementsQueue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotina para colocar as medições do dynamics na fila de integração com o prothues';

    /**
     * The console command variables custom.
     *
     * @var string
    */
    protected $origin_model    = '';
    protected $title           = 'Dynamics - Carga das Medições Para o protheus';
    protected $origin          = 'Dynamics - Medições';
    protected $origin_command  = null;
    protected $destiny_model   = null;
    protected $destiny         = 'Protheus - Medições';
    protected $destiny_command = 'Integration:DynamicsProtheusProcessingMeasurementsQueue';
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
        ->get(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_MEASUREMENTS_GET').'?$expand=gao_Unidade($select=fieto_name,gao_filialprotheus)&$filter=gao_lidoprotheus ne true');

        
        if(!$response->ok()) {
            $error = 'Erro ao executar a schedule '.$this->title.'.';
            $this->sendAlert($schedule->email, $error, $this->title);
            return $this->info($error);
        }   

        $measurements        = $response->json();
        $measurements        = (isset($measurements['value'])) ? $measurements['value'] : [];
        $totalRows           = 0; 
        $measurementsNumbers = [];

        foreach ($measurements as $key => $value) {

            //Busca os detalhes do contrato
            $contractsDetails = Http::withToken($this->getAccessToken())
            ->withOptions([
                "verify" => false
            ])
            ->get(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_CONTRACTS_DETAILS_GET').'?$filter=_salesorderid_value eq '.$value["_gao_pedido_value"]);

            if(!$contractsDetails->ok()) {
                $error = 'Erro ao executar a schedule '.$this->title.' para buscar os detalhes do contrato '.$value["gao_name"].'.';
                $this->sendAlert($schedule->email, $error, $this->title);
                //return $this->info($error);
            } 

            $contractsDetails = $contractsDetails->json();

            if(!isset($contractsDetails['value'])) {
                $error = 'Erro ao executar a schedule '.$this->title.' para buscar os detalhes do contrato '.$value["gao_name"].'.';
                $this->sendAlert($schedule->email, $error, $this->title);
                //return $this->info($error);
            }

            $data = [];
            
            $data["CNDMASTER"] = [
                "CND_NUMMED" => $value['gao_nummed'],
                "CND_FILIAL" => $value['gao_Unidade']['gao_filialprotheus'],
                "CND_CONTRA" => $value["_gao_pedido_value"],
                "CND_COMPET" => $value['gao_competencia'],
                "CND_REVISA" => $value['gao_numerodarevisao'],
                "CND_CONDPG" => $value['gao_condicaodepagamento'],
                "CND_XCODOR" => $value['gao_nummed'],
                "CND_XSISOR" => "DYNAMICS"                             
            ];


            //Busca os itens das medições
            $measurementsItens = Http::withToken($this->getAccessToken())
            ->withOptions([
                "verify" => false
            ])
            ->get(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_MEASUREMENTS_ITENS_GET').'?$filter=_gao_medicao_value eq '.$value["gao_medicoesid"]);


            if(!$measurementsItens->ok()) {
                $error = 'Erro ao executar a schedule '.$this->title.' para buscar os detalhes do contrato '.$value["gao_name"].'.';
                $this->sendAlert($schedule->email, $error, $this->title);
                //return $this->info($error);
            } 

            $measurementsItens = $measurementsItens->json();

            foreach ($measurementsItens['value'] as $key => $measurementsItem) {

                $contractProduct = Http::withToken($this->getAccessToken())
                ->withOptions([
                    "verify" => false
                ])
                ->get(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_CONTRACTS_DETAILS_PRODUCT_GET').'('.$measurementsItem['gao_produto'].')?$expand=fieto_centro_de_responsabilidade_id($select=fieto_name, gao_codcr,gao_coddn)');

                if(!$contractProduct->ok()) {
                    $error = 'Erro ao executar a schedule '.$this->title.' para buscar a medição do contrato '.$value["_gao_pedido_value"].'.';
                    $this->sendAlert($schedule->email, $error, $this->title);
                    //return $this->info($error);
                }

                $contractProduct = $contractProduct->json();
                $product         = str_replace(['.', '-', '/'], '', $contractProduct['fieto_centro_de_responsabilidade_id']['gao_codcr']);

                $data["CNEDETAIL"][] = [
                    "CNE_NUMMED" => $value['gao_nummed'],
                    "CNE_FILIAL" => $value['gao_Unidade']['gao_filialprotheus'],
                    "CNE_ITEM"   => str_pad(($measurementsItem['gao_numeroitem']), 3, '0', STR_PAD_LEFT),
                    "CNE_PRODUT" => $product,
                    "CNE_QUANT"  => $measurementsItem['gao_quantidademedida'],
                    "CNE_VLUNIT" => "",
                    "CNE_CC"     => "",
                    "CNE_CONTA"  => "",
                    "CNE_ITEMCT" => $product
                ];
            }            

            $integration_queue = Integration_queue::create(array(
                'origin_model'    => $this->origin_model,
                'origin_key'      => $value['gao_nummed'],
                'origin'          => $this->origin,
                'origin_command'  => $this->signature,
                'destiny_model'   => $this->destiny_model,
                'destiny_key'     => $value['gao_nummed'],
                'destiny'         => $this->destiny,
                'destiny_command' => $this->destiny_command,
                'properties'      => base64_encode(json_encode($data)),
                'status'          => 2
            ));

            $measurementsNumbers[] =  [
                "gao_medicoesid" => $value["gao_medicoesid"]
            ]; 

            $totalRows++;  
        } 

        if (!empty($measurementsNumbers)) {
            
            $response = Http::withOptions([
                "verify" => false
            ])
            ->post(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_MEASUREMENTS_UPDATE'), [
                "medicoes" => $measurementsNumbers
            ]);

            if($response->status() != '202') {
                $error = 'Erro ao executar atualizar os registros integrados na schedule '.$this->title.'.';
                $this->sendAlert($schedule->email, $error, $this->title);
                //return $this->info($error);
            }  
        }

        return $this->info('A Rotina '.$this->title.' foi executada com sucesso, foi colocado na fila  '.$totalRows.' registros para integração.');      
    }
}
