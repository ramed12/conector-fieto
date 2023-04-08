<?php

namespace App\Console\Commands\Dynamics;

use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use App\Model\Schedules;
use App\Model\Integration_queue;
use App\Model\From_to;
use App\Traits\AlertMail;
use Str;

class DynamicsProtheusProcessingClientsQueue extends Command
{
    use AlertMail;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Integration:DynamicsProtheusProcessingClientsQueue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotina para processar os clientes do dynamics na fila de integração com o prothues';

    /**
     * The console command variables custom.
     *
     * @var string
    */
    protected $origin_model    = '';
    protected $title           = 'Dynamics - Processamento dos Clientes Para o Protheus';
    protected $origin          = 'Dynamics - Clientes';
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

                $response = Http::withBasicAuth(env('WS_PROTHEUS_USERNAME', 'admin'), env('WS_PROTHEUS_PASSWORD', 'q1w2e3r4_t5'))
                ->post(env('WS_PROTHEUS_URL')."/clientes", [ 
                    "A1_NOME"     => substr(preg_replace('/(\'|")/', "", $properties['A1_NOME']), 0, 40),
                    "A1_NREDUZ"   => substr(preg_replace('/(\'|")/', "", $properties['A1_NREDUZ']), 0, 20),
                    "A1_PESSOA"   => $properties['A1_PESSOA'],
                    "A1_TIPO"     => $properties['A1_TIPO'],
                    "A1_END"      => substr(preg_replace('/(\'|")/', "", $properties['A1_END']), 0, 80),
                    "A1_BAIRRO"   => substr(preg_replace('/(\'|")/', "", $properties['A1_BAIRRO']), 0, 40),
                    "A1_CODPAIS"  => $properties['A1_CODPAIS'],
                    "A1_EST"      => $properties['A1_EST'],
                    "A1_MUN"      => $properties['A1_MUN'],
                    "A1_CEP"      => str_replace(["-"], "", $properties['A1_CEP']),
                    "A1_TEL"      => substr(str_replace(['-', '(', ')', '/'], '', $properties['A1_TEL']), 0, 15),
                    "A1_CGC"      => str_replace(["-", "/", "."], "", $properties['A1_CGC']),
                    "A1_CEINSS"   => $properties['A1_CEINSS'],
                    "A1_PAIS"     => $properties['A1_PAIS'],
                    "A1_INSCRM"   => $properties['A1_INSCRM'],
                    "A1_EMAIL"    => substr($properties['A1_EMAIL'], 0, 100),
                    "A1_INSCR"    => 'ISENTO',
                    "A1_XCODORI"  => $properties['A1_XCODORI'],
                    "A1_XSISORI"  => $properties['A1_XSISORI']
                ]);

                if($response->successful()) {
                    $value->status = 1;
                    $value->error_log = "";
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
