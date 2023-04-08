<?php

namespace App\Console\Commands\Dynamics;

use Illuminate\Console\Command;
use App\Model\Schedules;
use App\Model\Integration_queue;
use App\Model\From_to;
use Illuminate\Support\Facades\Http;
use App\Traits\DynamicsApi;
use App\Traits\AlertMail;
use DB;
use Str;
use Exception;

class DynamicsProtheusChargeContractsQueue extends Command
{

    use DynamicsApi, AlertMail;

    /**
     * The total number processed.
     *
     * @var string
    */
    public $totalRows;

    /**
     * The name and signature of the console command.
     *
     * @var string
    */
    protected $signature = 'Integration:DynamicsProtheusChargeContractsQueue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotina para colocar os contratos do dynamics na fila de integração com o prothues';

    /**
     * The console command variables custom.
     *
     * @var string
    */
    protected $origin_model    = '';
    protected $title           = 'Dynamics - Carga dos Contratos Para o protheus';
    protected $origin          = 'Dynamics - Contratos';
    protected $origin_command  = null;
    protected $destiny_model   = null;
    protected $destiny         = 'Protheus - Contratos';
    protected $destiny_command = 'Integration:DynamicsProtheusProcessingContractsQueue';
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
        $this->totalRows      = 0;
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
        
        //CONTRATOS PARA CRIAÇÃO NO PROTHEUS
        $this->getCreatedContracts($schedule);

        //CONTRATOS PARA CANCELAMENTO NO PROTHEUS
        $this->getCanceledContracts($schedule);

        return $this->info('A Rotina '.$this->title.' foi executada com sucesso, foi colocado na fila  '.$this->totalRows.' registros para integração.');      
    }

    protected function getCreatedContracts($schedule) {

        $response = Http::withToken($this->getAccessToken())
        ->withOptions([
            "verify" => false
        ])
        ->get(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_CONTRACTS_GET').'?$select=_gao_filialoperacional_value,fieto_iniciovigencia,fieto_fimdavigncia,gao_codigo_da_natureza,ordernumber,gao_unid_de_vigencia,gao_vignciadocontrato,transactioncurrencyid,paymenttermscode,fieto_tipo_de_pedido,gao_reajuste,gao_caucao,new_datadaassinaturadopedido,statuscode,fieto_numero_parcelas,gao_objeto, name&$filter=gao_lidoprotheus eq false');
      
        if(!$response->ok()) {
            $error = 'Erro ao executar a schedule '.$this->title.'.';
            $this->sendAlert($schedule->email, $error, $this->title);
            return $this->info($error);
        }   

        $contracts        = $response->json();
        
        foreach ($contracts['value'] as $key => $value) { 

            $contractName = explode('-', $value['name']);

            if($contractName[0] == 'SGT' || $value['statuscode'] == '275670008') {
            
                //Dados do cabeçalho do contrato
                $data              = [];            
                $data['CN9MASTER'] = [
                    "CN9_FILIAL"  => $value["_gao_filialoperacional_value"],                   
                    "CN9_DTINIC"  => date('d/m/Y', strtotime($value["fieto_iniciovigencia"])),         
                    "CN9_DTFIM"   => date('d/m/Y', strtotime($value["fieto_fimdavigncia"])),           
                    "CN9_NATURE"  => $value["gao_codigo_da_natureza"], 
                    "CN9_CONDPG"  => "002",     
                    "CN9_NUMERO"  => $value["ordernumber"],                  
                    "CN9_UNVIGE"  => (string) $value["gao_unid_de_vigencia"],         
                    "CN9_VIGE"    => "12",        
                    "CN9_MOEDA"   => "1",            
                    "CN9_TPCTO"   => "022",         
                    "CN9_FLGRJ"   => "2",                 
                    "CN9_FLGCAU"  => "2",                   
                    "CN9_ASSINA"  => date('d/m/Y',strtotime($value["new_datadaassinaturadopedido"])), 
                    "CN9_SITUAC"  => "05",                  
                    "CN9_XCODORI" => $value["salesorderid"],
                    "CN9_XREGP"   => "2",
                    "CN9_VLDCTR"  => "2",
                    "CN9_XSISORI" => 'DYNAMICS',
                    "CN9_OBJCTO"  => $value["gao_objeto"],
                    "CN9_XCODIN"  => $value["ordernumber"],
                    "CN9_XNAME"   => $value['name']
                ];

                //Busca os detalhes do contrato
                $contractsDetails = Http::withToken($this->getAccessToken())
                ->withOptions([
                    "verify" => false
                ])
                ->get(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_CONTRACTS_DETAILS_GET').'?$filter=_salesorderid_value eq '.$value["salesorderid"]);

                if(!$contractsDetails->ok()) {
                    $error = 'Erro ao executar a schedule '.$this->title.' para buscar os detalhes do contrato '.$value["ordernumber"].'.';
                    $this->sendAlert($schedule->email, $error, $this->title);
                    //return $this->info($error);
                } 

                $contractsDetails = $contractsDetails->json();

                if(!isset($contractsDetails['value'])) {
                    $error = 'Erro ao executar a schedule '.$this->title.' para buscar os detalhes do contrato '.$value["ordernumber"].'.';
                    $this->sendAlert($schedule->email, $error, $this->title);
                    //return $this->info($error);
                }

                $contractsDetailsItens = [];

                foreach ($contractsDetails['value'] as $key => $contractsDetail) {

                    $contractProduct = Http::withToken($this->getAccessToken())
                    ->withOptions([
                        "verify" => false
                    ])
                    ->get(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_CONTRACTS_DETAILS_PRODUCT_GET').'('.$contractsDetail['gao_produto_string'].')?$expand=fieto_centro_de_responsabilidade_id($select=fieto_name, gao_codcr,gao_coddn)');

                    if(!$contractProduct->ok()) {
                        $error = 'Erro ao executar a schedule '.$this->title.' para buscar o produto do contrato '.$value["ordernumber"].'.';
                        $this->sendAlert($schedule->email, $error, $this->title);
                        //return $this->info($error);
                    }

                    $contractProduct         = $contractProduct->json();

                    if (isset($contractProduct['value'])) {
                        continue;
                        $error = 'Erro ao executar a schedule '.$this->title.'produto não encontrado para o contrato '.$value["ordernumber"].'.';
                        $this->sendAlert($schedule->email, $error, $this->title);
                        //return $this->info($error);
                    }

                    $product                 = str_replace(['.', '-', '/'], '', $contractProduct['fieto_centro_de_responsabilidade_id']['gao_codcr']);
                    $contractsDetailsItens[] = [
                        'product'               => $product,
                        'gao_idsistemadeorigem' => $contractProduct['gao_idsistemadeorigem']
                    ];
                }

                //Busca a Filial
                $responseFilial = Http::withToken($this->getAccessToken())
                ->withOptions([
                    "verify" => false
                ])
                ->get(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_CONTRACTS_FILIAL_GET').'('.$value["_gao_filialoperacional_value"].')?$select=gao_filialprotheus');
              
                if(!$responseFilial->ok()) {
                    $error = 'Erro ao executar a schedule '.$this->title.' para buscar a filial do contrato '.$value["ordernumber"].'.';
                    $this->sendAlert($schedule->email, $error, $this->title);
                    //return $this->info($error);
                }  

                $responseFilial = $responseFilial->json();
                
                if(!isset($responseFilial['gao_filialprotheus'])) {
                    $error = 'Filial não encontrada para o contrato '.$value["ordernumber"].'.';
                    $this->sendAlert($schedule->email, $error, $this->title);
                    //return $this->info($error);
                    continue;
                }
                
                $data['CN9MASTER']['CN9_FILIAL'] = $responseFilial['gao_filialprotheus'];

                //Busca dados de cliente
                $responseClient = Http::withToken($this->getAccessToken())
                ->withOptions([
                    "verify" => false
                ])
                ->get(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_CONTRACTS_DADOSDOCLIENTES_GET').'?$filter=_gao_pedido_value eq '.$value["salesorderid"].'');

                if(!$responseClient->ok()) {
                    $error = 'Erro ao executar a schedule '.$this->title.' para buscar o cliente do contrato '.$value["ordernumber"].'.';
                    $this->sendAlert($schedule->email, $error, $this->title);
                    //return $this->info($error);
                }        

                $clients = $responseClient->json();

                $data['CNCDETAIL'] = [];  

                foreach ($clients['value'] as $key => $client) {
                    
                    $data['CNCDETAIL'][] = [
                        'CNC_CLIENT' => $client['gao_cnpj'],
                        'CNC_LOJACL' => $client['gao_codigo']
                    ];
                } 

                //Busca dados da planilha do contrato
                $responseDadosdaplanilhas = Http::withToken($this->getAccessToken())
                ->withOptions([
                    "verify" => false
                ])
                ->get(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_CONTRACTS_DADOSDAPLANILHAS_GET').'?$filter=_gao_pedido_value eq '.$value["salesorderid"].'');

                if(!$responseDadosdaplanilhas->ok()) {
                    $error = 'Erro ao executar a schedule '.$this->title.' para buscar o cliente do contrato '.$value["ordernumber"].'.';
                    $this->sendAlert($schedule->email, $error, $this->title);
                    //return $this->info($error);
                } 

                $dadosdaplanilhas  = $responseDadosdaplanilhas->json();
                $data['CNADETAIL'] = [];

                foreach ($dadosdaplanilhas['value'] as $spreadsheetsKey => $spreadsheets) {
                    
                    //Planilhas do contrato
                    $data['CNADETAIL'][] = [                   
                        'CNA_CONTRA' => $spreadsheets['gao_nrcontrato'],
                        'CNA_FILIAL' => $data['CN9MASTER']['CN9_FILIAL'],
                        'CNA_NUMERO' => str_pad(($spreadsheets['gao_nrplanilha']), 6, '0', STR_PAD_LEFT),
                        'CNA_DTINI'  => date('d/m/Y', strtotime($spreadsheets['gao_datainicio'])),
                        'CNA_DTFIM'  => date('d/m/Y', strtotime($spreadsheets['gao_datafinal'])),
                        'CNA_CLIENT' => str_replace(['/', '.', '-'], "", $spreadsheets['gao_codigodocliente']),
                        'CNA_LOJACL' => $spreadsheets['gao_lojadocliente'],
                        'CNA_TIPPLA' => "023",
                        'CNA_FLREAJ' => $spreadsheets['gao_indicasereajuste']
                    ];

                    //Busca dados do item da planilha do contrato
                    $responseDadositensdaplanilhas = Http::withToken($this->getAccessToken())
                    ->withOptions([
                        "verify" => false
                    ])
                    ->get(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_CONTRACTS_DADOSDOSITENSDASPLANILHAS_GET').'?$filter=_gao_dadosdaplanilha_value eq '.$spreadsheets["gao_dadosdaplanilhaid"]);

                    if(!$responseDadositensdaplanilhas->ok()) {
                        $error = 'Erro ao executar a schedule '.$this->title.' para buscar os itens das planilha do contrato '.$value["ordernumber"].'.';
                        $this->sendAlert($schedule->email, $error, $this->title);
                        //return $this->info($error);
                    } 

                    $dadositensdaplanilhas = $responseDadositensdaplanilhas->json();
                    $data['CNBDETAIL']     = [];
                    
                    foreach ($dadositensdaplanilhas['value'] as $spreadsheetsRowsKey => $spreadsheetsRows) {
                        
                        if (isset($spreadsheetsRows['gao_nrdoitem'])) {

                            $product = "";   
                            
                            foreach ($contractsDetailsItens as $key => $contractsDetailItem) {
                                
                                if($contractsDetailItem['gao_idsistemadeorigem'] == $spreadsheetsRows['gao_produto']) {
                                    
                                   $data['CNBDETAIL'][] = [
                                        'CNB_ITEM'   => str_pad(($spreadsheetsRows['gao_nrdoitem']), 3, '0', STR_PAD_LEFT),
                                        'CNB_NUMERO' => str_pad(($spreadsheets['gao_nrplanilha']), 6, '0', STR_PAD_LEFT),
                                        'CNB_PRODUT' => $contractsDetailItem['product'],
                                        'CNB_QUANT'  => $spreadsheetsRows['gao_quantidade'],
                                        'CNB_VLUNIT' => $spreadsheetsRows['gao_valorunitario'],
                                        'CNB_PEDTIT' => $spreadsheetsRows['gao_notafiscal'],
                                        'CNB_CC'     => '0',
                                        'CNB_ITEMCT' => $contractsDetailItem['product']
                                    ];
                                }
                            }     
                        }
                    }
                } 

                $integration_queue = Integration_queue::create(array(
                    'origin_model'     => $this->origin_model,
                    'origin_key'       => $value["ordernumber"],
                    'origin'           => $this->origin,
                    'origin_command'   => $this->signature,
                    'destiny_model'    => $this->destiny_model,
                    'destiny_key'      => $value["ordernumber"],
                    'destiny'          => $this->destiny,
                    'destiny_command'  => $this->destiny_command,
                    'properties'       => base64_encode(json_encode($this->getFromTo($data))),
                    'status'           => 2
                ));

                $this->totalRows++; 


                $response = Http::withOptions([
                    "verify" => false
                ])
                ->post(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_CONTRACTS_UPDATE'), [
                    "CONTRATOS" => [
                        [
                            "CN9_NUMERO" => $value["ordernumber"]
                        ]
                    ]
                ]);

                if($response->status() != '202') {
                    $error = 'Erro ao executar atualizar o registro '. $value["ordernumber"].' integrado na schedule '.$this->title.'.';
                    $this->sendAlert($schedule->email, $error, $this->title);
                    //return $this->info($error);
                }  
            }
        } 
    }

    protected function getCanceledContracts($schedule) {
  
        $contractsCanceled = Http::withOptions([
            "verify" => false
        ])
        ->post(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_CONTRACTS_CANCELADOS'), [
            "EXCLUIR" => true
        ]);        
      
        if(!$contractsCanceled->ok()) {
            $error = 'Erro ao buscar os contratos cancelados.';
            $this->sendAlert($schedule->email, $error, $this->title);
            return $this->info($error);
        }

        $contractsCanceledPedidos = $contractsCanceled->json();   

        foreach ($contractsCanceledPedidos as $key => $contractsCanceled) { 

            $response = Http::withToken($this->getAccessToken())
            ->withOptions([
                "verify" => false
            ])
            ->get(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_CONTRACTS_GET').'('.$contractsCanceled.')?$select=_gao_filialoperacional_value,fieto_iniciovigencia,fieto_fimdavigncia,gao_codigo_da_natureza,ordernumber,gao_unid_de_vigencia,gao_vignciadocontrato,transactioncurrencyid,paymenttermscode,fieto_tipo_de_pedido,gao_reajuste,gao_caucao,new_datadaassinaturadopedido,statuscode,fieto_numero_parcelas,gao_objeto, name');
          
            if(!$response->ok()) {
                $error = 'Erro ao executar a schedule '.$this->title.'.';
                $this->sendAlert($schedule->email, $error, $this->title);
                return $this->info($error);
            }   

            $value = $response->json();   

            //Dados do cabeçalho do contrato
            $data              = [];            
            $data['CN9MASTER'] = [
                "CN9_FILIAL"  => $value["_gao_filialoperacional_value"],                   
                "CN9_DTINIC"  => date('d/m/Y', strtotime($value["fieto_iniciovigencia"])),         
                "CN9_DTFIM"   => date('d/m/Y', strtotime($value["fieto_fimdavigncia"])),           
                "CN9_NATURE"  => $value["gao_codigo_da_natureza"], 
                "CN9_CONDPG"  => "002",     
                "CN9_NUMERO"  => $value["ordernumber"],                  
                "CN9_UNVIGE"  => (string) $value["gao_unid_de_vigencia"],         
                "CN9_VIGE"    => "12",        
                "CN9_MOEDA"   => "1",            
                "CN9_TPCTO"   => "022",         
                "CN9_FLGRJ"   => "2",                 
                "CN9_FLGCAU"  => "2",                   
                "CN9_ASSINA"  => date('d/m/Y',strtotime($value["new_datadaassinaturadopedido"])), 
                "CN9_SITUAC"  => "01",                  
                "CN9_XCODORI" => $value["salesorderid"],
                "CN9_XREGP"   => "2",
                "CN9_VLDCTR"  => "2",
                "CN9_XSISORI" => 'DYNAMICS',
                "CN9_OBJCTO"  => $value["gao_objeto"],
                "CN9_XCODIN"  => $value["ordernumber"],
                "CN9_XNAME"   => $value['name']
            ];

            //Busca os detalhes do contrato
            $contractsDetails = Http::withToken($this->getAccessToken())
            ->withOptions([
                "verify" => false
            ])
            ->get(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_CONTRACTS_DETAILS_GET').'?$filter=_salesorderid_value eq '.$value["salesorderid"]);

            if(!$contractsDetails->ok()) {
                $error = 'Erro ao executar a schedule '.$this->title.' para buscar os detalhes do contrato '.$value["ordernumber"].'.';
                $this->sendAlert($schedule->email, $error, $this->title);
                //return $this->info($error);
            } 

            $contractsDetails = $contractsDetails->json();

            if(!isset($contractsDetails['value'])) {
                $error = 'Erro ao executar a schedule '.$this->title.' para buscar os detalhes do contrato '.$value["ordernumber"].'.';
                $this->sendAlert($schedule->email, $error, $this->title);
                //return $this->info($error);
            }

            $contractsDetailsItens = [];

            foreach ($contractsDetails['value'] as $key => $contractsDetail) {

                $contractProduct = Http::withToken($this->getAccessToken())
                ->withOptions([
                    "verify" => false
                ])
                ->get(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_CONTRACTS_DETAILS_PRODUCT_GET').'('.$contractsDetail['gao_produto_string'].')?$expand=fieto_centro_de_responsabilidade_id($select=fieto_name, gao_codcr,gao_coddn)');

                if(!$contractProduct->ok()) {
                    $error = 'Erro ao executar a schedule '.$this->title.' para buscar o produto do contrato '.$value["ordernumber"].'.';
                    $this->sendAlert($schedule->email, $error, $this->title);
                    //return $this->info($error);
                }

                $contractProduct         = $contractProduct->json();

                if (isset($contractProduct['value'])) {
                    continue;
                    $error = 'Erro ao executar a schedule '.$this->title.'produto não encontrado para o contrato '.$value["ordernumber"].'.';
                    $this->sendAlert($schedule->email, $error, $this->title);
                    //return $this->info($error);
                }

                $product                 = str_replace(['.', '-', '/'], '', $contractProduct['fieto_centro_de_responsabilidade_id']['gao_codcr']);
                $contractsDetailsItens[] = [
                    'product'               => $product,
                    'gao_idsistemadeorigem' => $contractProduct['gao_idsistemadeorigem']
                ];
            }

            //Busca a Filial
            $responseFilial = Http::withToken($this->getAccessToken())
            ->withOptions([
                "verify" => false
            ])
            ->get(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_CONTRACTS_FILIAL_GET').'('.$value["_gao_filialoperacional_value"].')?$select=gao_filialprotheus');
          
            if(!$responseFilial->ok()) {
                $error = 'Erro ao executar a schedule '.$this->title.' para buscar a filial do contrato '.$value["ordernumber"].'.';
                $this->sendAlert($schedule->email, $error, $this->title);
                //return $this->info($error);
            }  

            $responseFilial = $responseFilial->json();
            
            if(!isset($responseFilial['gao_filialprotheus'])) {
                $error = 'Filial não encontrada para o contrato '.$value["ordernumber"].'.';
                $this->sendAlert($schedule->email, $error, $this->title);
                //return $this->info($error);
                continue;
            }
            
            $data['CN9MASTER']['CN9_FILIAL'] = $responseFilial['gao_filialprotheus'];

            //Busca dados de cliente
            $responseClient = Http::withToken($this->getAccessToken())
            ->withOptions([
                "verify" => false
            ])
            ->get(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_CONTRACTS_DADOSDOCLIENTES_GET').'?$filter=_gao_pedido_value eq '.$value["salesorderid"].'');

            if(!$responseClient->ok()) {
                $error = 'Erro ao executar a schedule '.$this->title.' para buscar o cliente do contrato '.$value["ordernumber"].'.';
                $this->sendAlert($schedule->email, $error, $this->title);
                //return $this->info($error);
            }        

            $clients = $responseClient->json();

            $data['CNCDETAIL'] = [];  

            foreach ($clients['value'] as $key => $client) {
                
                $data['CNCDETAIL'][] = [
                    'CNC_CLIENT' => $client['gao_cnpj'],
                    'CNC_LOJACL' => $client['gao_codigo']
                ];
            } 

            //Busca dados da planilha do contrato
            $responseDadosdaplanilhas = Http::withToken($this->getAccessToken())
            ->withOptions([
                "verify" => false
            ])
            ->get(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_CONTRACTS_DADOSDAPLANILHAS_GET').'?$filter=_gao_pedido_value eq '.$value["salesorderid"].'');

            if(!$responseDadosdaplanilhas->ok()) {
                $error = 'Erro ao executar a schedule '.$this->title.' para buscar o cliente do contrato '.$value["ordernumber"].'.';
                $this->sendAlert($schedule->email, $error, $this->title);
                //return $this->info($error);
            } 

            $dadosdaplanilhas  = $responseDadosdaplanilhas->json();
            $data['CNADETAIL'] = [];

            foreach ($dadosdaplanilhas['value'] as $spreadsheetsKey => $spreadsheets) {
                
                //Planilhas do contrato
                $data['CNADETAIL'][] = [                   
                    'CNA_CONTRA' => $spreadsheets['gao_nrcontrato'],
                    'CNA_FILIAL' => $data['CN9MASTER']['CN9_FILIAL'],
                    'CNA_NUMERO' => str_pad(($spreadsheets['gao_nrplanilha']), 6, '0', STR_PAD_LEFT),
                    'CNA_DTINI'  => date('d/m/Y', strtotime($spreadsheets['gao_datainicio'])),
                    'CNA_DTFIM'  => date('d/m/Y', strtotime($spreadsheets['gao_datafinal'])),
                    'CNA_CLIENT' => str_replace(['/', '.', '-'], "", $spreadsheets['gao_codigodocliente']),
                    'CNA_LOJACL' => $spreadsheets['gao_lojadocliente'],
                    'CNA_TIPPLA' => "023",
                    'CNA_FLREAJ' => $spreadsheets['gao_indicasereajuste']
                ];

                //Busca dados do item da planilha do contrato
                $responseDadositensdaplanilhas = Http::withToken($this->getAccessToken())
                ->withOptions([
                    "verify" => false
                ])
                ->get(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_CONTRACTS_DADOSDOSITENSDASPLANILHAS_GET').'?$filter=_gao_dadosdaplanilha_value eq '.$spreadsheets["gao_dadosdaplanilhaid"]);

                if(!$responseDadositensdaplanilhas->ok()) {
                    $error = 'Erro ao executar a schedule '.$this->title.' para buscar os itens das planilha do contrato '.$value["ordernumber"].'.';
                    $this->sendAlert($schedule->email, $error, $this->title);
                    //return $this->info($error);
                } 

                $dadositensdaplanilhas = $responseDadositensdaplanilhas->json();
                $data['CNBDETAIL']     = [];
                
                foreach ($dadositensdaplanilhas['value'] as $spreadsheetsRowsKey => $spreadsheetsRows) {
                    
                    if (isset($spreadsheetsRows['gao_nrdoitem'])) {

                        $product = "";   
                        
                        foreach ($contractsDetailsItens as $key => $contractsDetailItem) {
                            
                            if($contractsDetailItem['gao_idsistemadeorigem'] == $spreadsheetsRows['gao_produto']) {
                                
                               $data['CNBDETAIL'][] = [
                                    'CNB_ITEM'   => str_pad(($spreadsheetsRows['gao_nrdoitem']), 3, '0', STR_PAD_LEFT),
                                    'CNB_NUMERO' => str_pad(($spreadsheets['gao_nrplanilha']), 6, '0', STR_PAD_LEFT),
                                    'CNB_PRODUT' => $contractsDetailItem['product'],
                                    'CNB_QUANT'  => $spreadsheetsRows['gao_quantidade'],
                                    'CNB_VLUNIT' => $spreadsheetsRows['gao_valorunitario'],
                                    'CNB_PEDTIT' => $spreadsheetsRows['gao_notafiscal'],
                                    'CNB_CC'     => '0',
                                    'CNB_ITEMCT' => $contractsDetailItem['product']
                                ];
                            }
                        }     
                    }
                }
            } 

            $integration_queue = Integration_queue::create(array(
                'origin_model'     => $this->origin_model,
                'origin_key'       => $value["ordernumber"],
                'origin'           => $this->origin,
                'origin_command'   => $this->signature,
                'destiny_model'    => $this->destiny_model,
                'destiny_key'      => $value["ordernumber"],
                'destiny'          => $this->destiny,
                'destiny_command'  => $this->destiny_command,
                'properties'       => base64_encode(json_encode($this->getFromTo($data))),
                'status'           => 2
            ));

            $this->totalRows++;             
        }  
    }

    protected function getFromTo($properties) {

        $propertiesArrayMap = array_map(function($key, $value)  use ($properties) {

            if (is_array($value) || is_object($value)) {
                
                $value = (array) $value;

                foreach ($value as $ValueKey => $item) {

                    if (is_array($item) || is_object($item)) {
                       
                       $item = (array) $item;

                        foreach ($item as $i => $row) {

                           if(is_array($row) || is_object($row)) {

                                foreach ($row as $rowKey => $r) {
                                    $from_to = From_to::where('field', (string) $rowKey)->where('value_origin', $r)->where(function($query)  use ($properties) {
                                        $query->where('filial', (string) $properties['CN9MASTER']['CN9_FILIAL'])
                                        ->orWhere('filial', '');
                                    })->first();

                                    if (!empty($from_to)) {
                                        $row[$rowKey] = $from_to->value_destiny;                                           
                                    }
                                }

                                $item[$i] = $row;

                           } else {

                                $from_to = From_to::where('field', (string) $i)->where('value_origin', $row)
                                ->where(function($query)  use ($properties) {
                                    $query->where('filial', (string) $properties['CN9MASTER']['CN9_FILIAL'])
                                    ->orWhere('filial', '');
                                })->first();

                                if (!empty($from_to)) {
                                    $item[$i] =  $from_to->value_destiny;
                                }
                            }                               
                        }

                        $value[$ValueKey] = $item;

                    }else {

                       $from_to = From_to::where('field', (string) $ValueKey)->where('value_origin', $item)
                       ->where(function($query)  use ($properties) {
                            $query->where('filial', (string) $properties['CN9MASTER']['CN9_FILIAL'])
                            ->orWhere('filial', '');
                        })->first();

                        if (!empty($from_to)) {
                            $value[$ValueKey] =  $from_to->value_destiny;
                        }  
                    }  
                }

            } else {
                
                $from_to = From_to::where('field', $key)->where('value_origin', $value)
                ->where(function($query)  use ($properties) {
                    $query->where('filial', $properties['CN9MASTER']['CN9_FILIAL'])
                    ->orWhere('filial', '');
                })->first();

                if (!empty($from_to)) {
                    $value =  $from_to->value_destiny;
                }
            }  

            return [$key =>  $value === null ? '' : $value];

        }, array_keys($properties), $properties);

        $properties = array_combine(
            array_keys($properties), 
            array_map(function($key, $value){ 
                return $value[$key]; 
            }, array_keys($properties), $propertiesArrayMap)
        ); 

        return $properties;
    }
}
