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

class OrquestraDinamicsChargeClientsQueue extends Command
{
    use OrquestraApi, AlertMail;

    /**
     * The name and signature of the console command.
     *
     * @var string
    */
    protected $signature = 'Integration:OrquestraDinamicsChargeClientsQueue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotina para colocar os clientes do Orquestra na fila de integração com o dynamics';

    /**
     * The console command variables custom.
     *
     * @var string
    */
    protected $origin_model    = '';
    protected $title           = 'Orquestra - Carga dos Clientes Para o Dynamics';
    protected $origin          = 'Orquestra - Clientes';
    protected $origin_command  = null;
    protected $destiny_model   = null;
    protected $destiny         = 'Dynamics - Clientes';
    protected $destiny_command = 'Integration:OrquestraDinamicsProcessingClientsQueue';
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
        ->get(env('WS_ORQUESTRA_ENDPOINT')."/Pessoa");

        if (!$response->ok()) {
            $error = 'Erro ao executar a schedule '.$this->title.'.';
            $this->sendAlert($schedule->email, $error, $this->title);
            return $this->info($error);
        }        

        $customers = $response->json();

        $totalRows = 0; 

        foreach ($customers['data'] as $key => $value) {
            
            $data = [
                "A1_FILIAL"  => $value['matrizFilial'],
                "A1_COD"     => $value['id'],    
                "A1_LOJA"    => '01', //Não possui na integração   
                "A1_NOME"    => $value['razaoSocial'],  
                "A1_NREDUZ"  => $value['nome'], 
                "A1_PESSOA"  => ($value['tipoPessoa'] == 1) ? 'J' : 'F', 
                "A1_TIPO"    => 'F', //Não possui na integração    
                "A1_END"     => $value['enderecoRua'],    
                "A1_BAIRRO"  => $value['enderecoBairro'], 
                "A1_EST"     => $value['estado'],    
                "A1_COD_MUN" => $value['enderecoCidadeId'],
                "A1_MUN"     => $value['enderecoCidade'],    
                "A1_CEP"     => $value['enderecoCep'],    
                "A1_TEL"     => $value['contatoCelular'],    
                "A1_CGC"     => ($value['tipoPessoa'] == 1) ? $value['cnpj'] : $value['cpf'],    
                "A1_PAIS"    => '105', //Não possui na integração  
                "A1_INSCRM"  => $value['inscricaoMunicipal'],
                "A1_CNAE"    => '', 
                "A1_EMAIL"   => $value['contatoEmail'],  
                "A1_INSCR"   => $value['inscricaoEstadual'] 
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
