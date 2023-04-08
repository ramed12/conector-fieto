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

class DynamicsProtheusProcessingMeasurementsQueue extends Command
{

    use AlertMail;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Integration:DynamicsProtheusProcessingMeasurementsQueue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotina para processar as medições do dynamics na fila de integração com o prothues';

    /**
     * The console command variables custom.
     *
     * @var string
    */
    protected $origin_model    = '';
    protected $title           = 'Dynamics - Processamento das Medições Para o Protheus';
    protected $origin          = 'Dynamics - Medições';
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

        $data          = Integration_queue::where('destiny_command', $this->signature)->take($schedule->pagination)->where('status',2)->get();
        $dataIds       = $data->pluck('id');
        $totalRows     = 0;
        $totalRowsErro = 0;

        Integration_queue::whereIn('id', $dataIds)->update([
            'status' => '1'
        ]);

        foreach ($data as $key => $value) {

            $properties = (array) json_decode(base64_decode($value->properties));

            try {

                $data = [];
            
                $data["CNDMASTER"] = [
                    "CND_NUMMED" => (string) $properties['CNDMASTER']->CND_NUMMED,
                    "CND_FILIAL" => (string) $properties['CNDMASTER']->CND_FILIAL,
                    "CND_CONTRA" => (string) $properties['CNDMASTER']->CND_CONTRA,
                    "CND_COMPET" => (string) $properties['CNDMASTER']->CND_COMPET,
                    "CND_REVISA" => (string) $properties['CNDMASTER']->CND_REVISA,
                    "CND_CONDPG" => (string) $properties['CNDMASTER']->CND_CONDPG,
                    "CND_XCODOR" => (string) @$properties['CNDMASTER']->CND_XCODOR,
                    "CND_XSISOR" => (string) @$properties['CNDMASTER']->CND_XSISOR,
                    "CND_TOTALITENS" => 0       
                ];

                $data["CNEDETAIL"] = [];

                foreach ($properties["CNEDETAIL"] as $key => $cnedetail) {
                    
                    $data["CNEDETAIL"][$key] = [
                        "CNE_FILIAL"   => (string) $cnedetail->CNE_FILIAL,
                        "CNE_PRODUT"   => (string) @$cnedetail->CNE_PRODUT,
                        "CNE_ITEM"     => (string) $cnedetail->CNE_ITEM,
                        "CNE_QUANT"    => (string) $cnedetail->CNE_QUANT,
                        //"CNE_VLUNIT" => $cnedetail->CNE_VLUNIT,
                        //"CNE_CC"     => $cnedetail->CNE_CC,
                        //"CNE_CONTA"  => $cnedetail->CNE_CONTA,
                        "CNE_ITEMCT"   => (string) $cnedetail->CNE_ITEMCT
                    ];

                    $data["CNDMASTER"]["CND_TOTALITENS"] += $cnedetail->CNE_QUANT;
                }                

                $response = Http::withBasicAuth(env('WS_PROTHEUS_USERNAME', 'admin'), env('WS_PROTHEUS_PASSWORD', 'q1w2e3r4_t5'))
                ->post(env('WS_PROTHEUS_URL')."/medicoes", $data);

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
