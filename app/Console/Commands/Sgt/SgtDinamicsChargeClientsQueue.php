<?php

namespace App\Console\Commands\Sgt;

use Illuminate\Console\Command;
use App\Model\Schedules;
use App\Model\SchedulesLog;
use App\Model\Integration_queue;
use Illuminate\Support\Facades\Http;
use App\Notifications\IntegrationFailure;
use Illuminate\Notifications\Notification;
use App\Traits\AlertMail;
use DateTime;
use DB;
use Str;

class SgtDinamicsChargeClientsQueue extends Command
{
    use AlertMail;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
    */
    protected $signature = 'Integration:SgtDinamicsChargeClientsQueue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotina para colocar os clientes do Sgt na fila de integração com o dynamics';

    /**
     * The console command variables custom.
     *
     * @var string
    */
    protected $origin_model    = '';
    protected $title           = 'SGT - Carga dos Clientes Para o Dynamics';
    protected $origin          = 'SGT - Clientes';
    protected $origin_command  = null;
    protected $destiny_model   = null;
    protected $destiny         = 'Dynamics - Clientes';
    protected $destiny_command = 'Integration:SgtDinamicsProcessingClientsQueue';
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
				'command' => $this->signature
            ));
		}
		
		$startDate = $lastExecSchedule->last_exec;

        $response = Http::withToken(env('WS_SGT_API_TOKEN'))
        ->withOptions([
            "verify" => false
        ])
        ->acceptJson()
        ->get(env('WS_SGT_API_ENDPOINT')."/atendimentos?deDataModificacao=".urlencode($startDate)."&ateDataModificacao=".urlencode($endDate));

        if (!$response->ok()) {
            $error = 'Erro ao executar a schedule '.$this->title.'.';
            $this->sendAlert($schedule->email, $error, $this->title);
            return $this->info($error);
        }   

        $response      = $response->json();
        $totalRows     = 0;
        
        if(!empty($response['Atendimentos'])) {

            $contracts     = $response['Atendimentos']['Atendimento']; 
           
            $contractsData = []; 

            if (isset($contracts['idAtendimento'])) {
                $contractsData[] = $contracts;
            }else {
                $contractsData = $contracts;
            }

            foreach ($contractsData as $key => $value) {
                
				// Aguarda meio segundo antes de realizar a próxima requisição.
				//sleep(.2);
				
                $response = Http::withToken(env('WS_SGT_API_TOKEN'))
                ->withOptions([
                    "verify" => false
                ])
                ->acceptJson()
                ->get(env('WS_SGT_API_ENDPOINT')."/clientes?cpfcnpj=".$value['cliente']);

                if (!$response->ok()) {					
                    return $this->info('Erro ao executar a schedule '.$this->title.'.');
                }
				
                $response        = $response->json();
                $customer        = $response['Clientes']['Cliente'];
                $customerAddress = [];
				
				if(isset($customer['id'])) {
					
					$data = [
						"A1_FILIAL"  => '',//Não possui na integração  
						"A1_COD"     => $customer['id'],    
						"A1_LOJA"    => '01', //Não possui na integração   
						"A1_NOME"    => $customer['razaoSocial'],  
						"A1_NREDUZ"  => $customer['nomeFantasia'], 
						"A1_PESSOA"  => ($customer['tipoPessoa'] == 1) ? 'J' : 'F', 
						"A1_TIPO"    => 'F', //Não possui na integração  
						"A1_TEL"     => '', //Não possui na integração   
						"A1_CGC"     => $customer['cpfcnpj'],    
						"A1_PAIS"    => '105', //Não possui na integração  
						"A1_INSCRM"  => '', //Não possui na integração
						"A1_EMAIL"   => '',//Não possui na integração  
						"A1_INSCR"   => $customer['inscricaoEstadual'],
						"A1_CNAE"    => "4120400",//str_replace(['-', '.', '/'], "", $customer['cnae']),
						"A1_XCODORI" => "SGT",
						"A1_XSISORI" => $customer["id"]
					];

					if (isset($customer['Enderecos'])) {

						if (isset($customer['Enderecos']['Endereco']['isPrincipal'])) {
						
							if($customer['Enderecos']['Endereco']['isPrincipal']) {
								$data['A1_END']        = $customer['Enderecos']['Endereco']['logradouro'];    
								$data['A1_BAIRRO']     = $customer['Enderecos']['Endereco']['bairro']; 
								$data['A1_EST']        = $customer['Enderecos']['Endereco']['unidadeFederativa'];    
								$data['A1_COD_MUN']    = $customer['Enderecos']['Endereco']['codigoIBGEMunicipio'];
								$data['A1_MUN']        = $customer['Enderecos']['Endereco']['cidade'];    
								$data['A1_CEP']        = $customer['Enderecos']['Endereco']['cep'];   
								$data['A1_COMPLEMENT'] = $customer['Enderecos']['Endereco']['complemento'];
							}

						} else {

							if (isset($customer['Enderecos']['Endereco'])) {
								
								foreach ($customer['Enderecos']['Endereco'] as $key => $address) {

									if($address['isPrincipal']) {
										$data['A1_END']        = $address['logradouro'];    
										$data['A1_BAIRRO']     = $address['bairro']; 
										$data['A1_EST']        = $address['unidadeFederativa'];    
										$data['A1_COD_MUN']    = $address['codigoIBGEMunicipio'];
										$data['A1_MUN']        = $address['cidade'];    
										$data['A1_CEP']        = $address['cep']; 
										$data['A1_COMPLEMENT'] =   $address['complemento']; 
									}
								}
							}else {
								$data['A1_END']        = "";         
								$data['A1_BAIRRO']     = "";   
								$data['A1_EST']        = "";         
								$data['A1_COD_MUN']    = "";  
								$data['A1_MUN']        = "";        
								$data['A1_CEP']        = "";
								$data['A1_COMPLEMENT'] = "";

							}                    
						} 
						
					}else {
						$data['A1_END']        = "";         
						$data['A1_BAIRRO']     = "";   
						$data['A1_EST']        = "";         
						$data['A1_COD_MUN']    = "";  
						$data['A1_MUN']        = "";        
						$data['A1_CEP']        = "";
						$data['A1_COMPLEMENT'] = "";
					}                  

					$integration_queue = Integration_queue::create(array(
						'origin_model'    => $this->origin_model,
						'origin_key'      => $customer['id'],
						'origin'          => $this->origin,
						'origin_command'  => $this->signature,
						'destiny_model'   => $this->destiny_model,
						'destiny_key'     => $customer['id'],
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
