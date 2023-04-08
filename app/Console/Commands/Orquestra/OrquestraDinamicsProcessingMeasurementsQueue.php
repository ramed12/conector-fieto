<?php

namespace App\Console\Commands\Orquestra;

use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use App\Model\Schedules;
use App\Model\Integration_queue;
use App\Model\From_to;
use App\Traits\DynamicsApi;
use App\Traits\AlertMail;
use Str;

class OrquestraDinamicsProcessingMeasurementsQueue extends Command
{
    use DynamicsApi, AlertMail;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Integration:OrquestraDinamicsProcessingMeasurementsQueue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotina para processar os medições do Orquestra na fila de integração com o dynamics';

    /**
     * The console command variables custom.
     *
     * @var string
    */
    protected $origin_model    = '';
    protected $title           = 'Orquestra - Processamento dos Medições Para o Dynamics';
    protected $origin          = 'Orquestra - Medições';
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

                $data = [
                    "CND_FILIAL" => (string) $properties['CND_FILIAL'],
                    "CND_CONTRA" => (string) $properties['CND_CONTRA'],
                    "CND_COMPET" => (string) $properties['CND_COMPET'],
                    "CND_REVISA" => (string) $properties['CND_REVISA'],
                    "CND_CONDPG" => (string) $properties['CND_CONDPG']               
                ];

                $data['DADOSMEDICAO'] = [];

                foreach ($properties['DADOSMEDICAO'] as $key => $valueCne) {
                    
                    $data['DADOSMEDICAO'][] = [
                        "CNE_FILIAL" => (string) $valueCne->CNE_FILIAL,
                        "CNE_ITEM"   => (string) $valueCne->CNE_ITEM,
                        "CNE_QUANT"  => (string) $valueCne->CNE_QUANT,
                        "CNE_VLUNIT" => (string) $valueCne->CNE_VLUNIT,
                        "CNE_CC"     => (string) $valueCne->CNE_CC,
                        "CNE_CONTA"  => (string) $valueCne->CNE_CONTA,
                        "CNE_ITEMCT" => (string) $valueCne->CNE_ITEMCT
                    ];
                }                

                $response = Http::withOptions([
                    "verify" => false
                ])
                ->post(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_MEASUREMENTS_STORE'), $properties);

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
