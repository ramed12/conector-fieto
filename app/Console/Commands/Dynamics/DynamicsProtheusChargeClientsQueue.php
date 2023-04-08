<?php

namespace App\Console\Commands\Dynamics;

use Illuminate\Console\Command;
use App\Model\Schedules;
use App\Model\Integration_queue;
use App\Model\From_to;
use Illuminate\Support\Facades\Http;
use App\Traits\AlertMail;
use DB;
use Str;

class DynamicsProtheusChargeClientsQueue extends Command
{

    use AlertMail;

    /**
     * The name and signature of the console command.
     *
     * @var string
    */
    protected $signature = 'Integration:DynamicsProtheusChargeClientsQueue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotina para colocar os clientes do dynamics na fila de integração com o prothues';

    /**
     * The console command variables custom.
     *
     * @var string
    */
    protected $origin_model    = '';
    protected $title           = 'Dynamics - Carga dos Clientes Para o Protheus';
    protected $origin          = 'Dynamics - Clientes';
    protected $origin_command  = null;
    protected $destiny_model   = null;
    protected $destiny         = 'Protheus - Clientes';
    protected $destiny_command = 'Integration:DynamicsProtheusProcessingClientsQueue';
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

        $response = Http::withOptions([
            "verify" => false
        ])
        ->post(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_CUSTOMERS_GET'), [
            "data" => date('Y-m-d H:i:s', strtotime('-1 days', strtotime(date('Y-m-d H:i:s'))))
        ]);

        if(!$response->ok()) {
            $error = 'Erro ao executar a schedule '.$this->title.'.';
            $this->sendAlert($schedule->email, $error, $this->title);
            return $this->info($error);
        }        

        $customers = $response->json();       
        $totalRows = 0; 
        $cnpjs     = [];

        foreach ($customers as $key => $value) {

            $data = [  
                "A1_FILIAL"  => "",              
                "A1_NOME"    => $value['A1_NREDUZ'], //$value['A1_NOME'],  
                "A1_NREDUZ"  => Str::limit($value['A1_NREDUZ'], 20, false), 
                "A1_PESSOA"  => $value['A1_PESSOA'], 
                "A1_TIPO"    => $value['A1_TIPO'],   
                "A1_END"     => $value['A1_END'],    
                "A1_BAIRRO"  => $value['A1_BAIRRO'], 
                "A1_CODPAIS" => "01058",
                "A1_EST"     => $value['A1_EST'],    
                "A1_MUN"     => $value['A1_MUN'],    
                "A1_CEP"     => str_replace(['.',',', '-'], '', $value['A1_CEP']),    
                "A1_TEL"     => $value['A1_TEL'],    
                "A1_CGC"     => $value['A1_CGC'],
                "A1_CEINSS"  => '',//$value['A1_CEINSS'],    
                "A1_PAIS"    => "105",//$value['A1_PAIS'],  
                "A1_INSCRM"  => $value['A1_INSCRM'], 
                "A1_EMAIL"   => $value['A1_EMAIL'],  
                "A1_INSCR"   => $value['A1_INSCR'],
                "A1_XCODORI" => $value['A1_COD'],
                "A1_XSISORI" => "DYNAMICS",
            ]; 

            //Realiza o de/para das informações
            $properties = $data;

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
                                            $query->where('filial', (string) $properties['A1_FILIAL'])
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
                                        $query->where('filial', (string) $properties['A1_FILIAL'])
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
                                $query->where('filial', (string) $properties['A1_FILIAL'])
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
                        $query->where('filial', $properties['A1_FILIAL'])
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

            $integration_queue = Integration_queue::create(array(
                'origin_model'    => $this->origin_model,
                'origin_key'      => $properties["A1_CGC"],
                'origin'          => $this->origin,
                'origin_command'  => $this->signature,
                'destiny_model'   => $this->destiny_model,
                'destiny_key'     => $properties["A1_CGC"],
                'destiny'         => $this->destiny,
                'destiny_command' => $this->destiny_command,
                'properties'      => base64_encode(json_encode($properties)),
                'status'          => 2
            ));

            $cnpjs[] = [
                "cnpj" => $properties["A1_CGC"]
            ];

            $totalRows++;  
        } 

        if (!empty($cnpjs)) {
            
            $response = Http::withOptions([
                "verify" => false
            ])
            ->post(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_CUSTOMERS_UPDATE'), [
                "clientes" => $cnpjs
            ]);

            if($response->status() != '202') {
                $error = 'Erro ao executar atualizar os registros integrados na schedule '.$this->title.'.';
                $this->sendAlert($schedule->email, $error, $this->title);
                return $this->info($error);
            }  
        }

        return $this->info('A Rotina '.$this->title.' foi executada com sucesso, foi colocado na fila  '.$totalRows.' registros para integração.');      
    }
}
