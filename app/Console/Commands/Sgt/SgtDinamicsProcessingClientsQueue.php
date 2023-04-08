<?php

namespace App\Console\Commands\Sgt;

use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use App\Model\Schedules;
use App\Model\Integration_queue;
use App\Model\From_to;
use App\Traits\AlertMail;
use Str;

class SgtDinamicsProcessingClientsQueue extends Command
{
    use AlertMail;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Integration:SgtDinamicsProcessingClientsQueue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotina para processar os clientes do Sgt na fila de integração com o dynamics';

    /**
     * The console command variables custom.
     *
     * @var string
    */
    protected $origin_model    = '';
    protected $title           = 'SGT - Processamento dos Clientes Para o Dynamics';
    protected $origin          = 'SGT - Clientes';
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
				// Aguarda um mili segundo antes de realizar a próxima requisição.
				//sleep(.1);
				
                $response = Http::withOptions([
                    "verify" => false
                ])
                ->post(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_CUSTOMERS_STORE'), [
                    //"A1_FILIAL"   => $properties['A1_FILIAL'],
                    "A1_COD"        => (string) $properties['A1_COD'],    
                    "A1_LOJA"       => (string) $properties['A1_LOJA'],  
                    "A1_NOME"       => (string) $properties['A1_NOME'],  
                    "A1_NREDUZ"     => (string) $properties['A1_NREDUZ'], 
                    "A1_PESSOA"     => (string) $properties['A1_PESSOA'], 
                    "A1_TIPO"       => (string) $properties['A1_TIPO'],   
                    "A1_END"        => (string) $properties['A1_END'], 
                    //"A1_COMPLEMENT" => (string) $properties['A1_COMPLEMENT'],   
                    "A1_BAIRRO"     => (string) $properties['A1_BAIRRO'], 
                    "A1_EST"        => (string) $properties['A1_EST'],    
                    "A1_COD_MUN"    => (string) $properties['A1_COD_MUN'],
                    "A1_MUN"        => (string) $properties['A1_MUN'],    
                    "A1_CEP"        => (string) $properties['A1_CEP'],    
                    "A1_TEL"        => (string) $properties['A1_TEL'],    
                    "A1_CGC"        => (string) $properties['A1_CGC'],    
                    "A1_PAIS"       => (string) $properties['A1_PAIS'],  
                    "A1_INSCRM"     => (string) $properties['A1_INSCRM'], 
                    "A1_EMAIL"      => (string) $properties['A1_EMAIL'],  
                    "A1_INSCR"      => (string) $properties['A1_INSCR'],
                    "A1_CNAE"       => (string) $properties['A1_CNAE'],
                    "A1_XCODORI"    => (string) $properties["A1_XCODORI"],
                    "A1_XSISORI"    => (string) $properties["A1_XSISORI"]
                ]);
				
				// Repete +2 vezes a requisição para o caso de falha
				if (!$response->successful()) {	
					//sleep(.1);
					$response = Http::withOptions([
						"verify" => false
					])
					->post(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_CUSTOMERS_STORE'), [
						//"A1_FILIAL"   => $properties['A1_FILIAL'],
						"A1_COD"        => (string) $properties['A1_COD'],    
						"A1_LOJA"       => (string) $properties['A1_LOJA'],  
						"A1_NOME"       => (string) $properties['A1_NOME'],  
						"A1_NREDUZ"     => (string) $properties['A1_NREDUZ'], 
						"A1_PESSOA"     => (string) $properties['A1_PESSOA'], 
						"A1_TIPO"       => (string) $properties['A1_TIPO'],   
						"A1_END"        => (string) $properties['A1_END'], 
						//"A1_COMPLEMENT" => (string) $properties['A1_COMPLEMENT'],   
						"A1_BAIRRO"     => (string) $properties['A1_BAIRRO'], 
						"A1_EST"        => (string) $properties['A1_EST'],    
						"A1_COD_MUN"    => (string) $properties['A1_COD_MUN'],
						"A1_MUN"        => (string) $properties['A1_MUN'],    
						"A1_CEP"        => (string) $properties['A1_CEP'],    
						"A1_TEL"        => (string) $properties['A1_TEL'],    
						"A1_CGC"        => (string) $properties['A1_CGC'],    
						"A1_PAIS"       => (string) $properties['A1_PAIS'],  
						"A1_INSCRM"     => (string) $properties['A1_INSCRM'], 
						"A1_EMAIL"      => (string) $properties['A1_EMAIL'],  
						"A1_INSCR"      => (string) $properties['A1_INSCR'],
						"A1_CNAE"       => (string) $properties['A1_CNAE'],
						"A1_XCODORI"    => (string) $properties["A1_XCODORI"],
						"A1_XSISORI"    => (string) $properties["A1_XSISORI"]
					]);
				}
				
				if (!$response->successful()) {	
					//sleep(.1);
					$response = Http::withOptions([
						"verify" => false
					])
					->post(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_CUSTOMERS_STORE'), [
						//"A1_FILIAL"   => $properties['A1_FILIAL'],
						"A1_COD"        => (string) $properties['A1_COD'],    
						"A1_LOJA"       => (string) $properties['A1_LOJA'],  
						"A1_NOME"       => (string) $properties['A1_NOME'],  
						"A1_NREDUZ"     => (string) $properties['A1_NREDUZ'], 
						"A1_PESSOA"     => (string) $properties['A1_PESSOA'], 
						"A1_TIPO"       => (string) $properties['A1_TIPO'],   
						"A1_END"        => (string) $properties['A1_END'], 
						//"A1_COMPLEMENT" => (string) $properties['A1_COMPLEMENT'],   
						"A1_BAIRRO"     => (string) $properties['A1_BAIRRO'], 
						"A1_EST"        => (string) $properties['A1_EST'],    
						"A1_COD_MUN"    => (string) $properties['A1_COD_MUN'],
						"A1_MUN"        => (string) $properties['A1_MUN'],    
						"A1_CEP"        => (string) $properties['A1_CEP'],    
						"A1_TEL"        => (string) $properties['A1_TEL'],    
						"A1_CGC"        => (string) $properties['A1_CGC'],    
						"A1_PAIS"       => (string) $properties['A1_PAIS'],  
						"A1_INSCRM"     => (string) $properties['A1_INSCRM'], 
						"A1_EMAIL"      => (string) $properties['A1_EMAIL'],  
						"A1_INSCR"      => (string) $properties['A1_INSCR'],
						"A1_CNAE"       => (string) $properties['A1_CNAE'],
						"A1_XCODORI"    => (string) $properties["A1_XCODORI"],
						"A1_XSISORI"    => (string) $properties["A1_XSISORI"]
					]);					
				}

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
