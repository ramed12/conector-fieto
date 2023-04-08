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

class DynamicsProtheusProcessingContractsQueue extends Command
{
    use AlertMail;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Integration:DynamicsProtheusProcessingContractsQueue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotina para processar os contratos do dynamics na fila de integração com o prothues';

    /**
     * The console command variables custom.
     *
     * @var string
    */
    protected $origin_model    = '';
    protected $title           = 'Dynamics - Processamento dos Contratos Para o Protheus';
    protected $origin          = 'Dynamics - Contratos';
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

                $data = [];            
                $data['CN9MASTER'][] = [
                    "CN9_FILIAL"  => (string) $properties['CN9MASTER']->CN9_FILIAL,                   
                    "CN9_DTINIC"  => $properties['CN9MASTER']->CN9_DTINIC,         
                    "CN9_DTFIM"   => $properties['CN9MASTER']->CN9_DTINIC,           
                    "CN9_NATURE"  => $properties['CN9MASTER']->CN9_NATURE,
                    "CN9_CONDPG"  => (string) $properties['CN9MASTER']->CN9_CONDPG,       
                    "CN9_NUMERO"  => (string) $properties['CN9MASTER']->CN9_NUMERO,                  
                    "CN9_UNVIGE"  => (string) $properties['CN9MASTER']->CN9_UNVIGE,         
                    "CN9_VIGE"    => (string) $properties['CN9MASTER']->CN9_VIGE,        
                    "CN9_MOEDA"   => (string) $properties['CN9MASTER']->CN9_MOEDA,            
                    "CN9_TPCTO"   => (string) $properties['CN9MASTER']->CN9_TPCTO,         
                    "CN9_FLGREJ"  => (string) $properties['CN9MASTER']->CN9_FLGRJ,                 
                    "CN9_FLGCAU"  => (string) $properties['CN9MASTER']->CN9_FLGCAU,                   
                    "CN9_ASSINA"  => (string) $properties['CN9MASTER']->CN9_ASSINA, 
                    "CN9_SITUAC"  => (string) $properties['CN9MASTER']->CN9_SITUAC,        
                    "CN9_XCODOR"  => (string) $properties['CN9MASTER']->CN9_XCODORI,
                    "CN9_XSISOR"  => (string) $properties['CN9MASTER']->CN9_XSISORI,
                    "CN9_XREGP"   => (string) $properties['CN9MASTER']->CN9_XREGP,
                    "CN9_VLDCTR"  => (string) $properties['CN9MASTER']->CN9_VLDCTR,
                    "CN9_OBJCTO"  => (string) $properties['CN9MASTER']->CN9_OBJCTO,                    
                    "CN9_XCODIN"  => (string) $properties['CN9MASTER']->CN9_XCODIN,
                    "CN9_XNAME"   => (string) $properties['CN9MASTER']->CN9_XNAME
                ];

                $data['CNCDETAIL'] = [];  

                foreach ($properties['CNCDETAIL'] as $key => $client) {
                    
                    $data['CNCDETAIL'][] = [
                        'CNC_CLIENT' => (string) $client->CNC_CLIENT,
                        'CNC_LOJACL' => (string) $client->CNC_LOJACL
                    ];
                } 

                $data['CNADETAIL'] = [];

                foreach ($properties['CNADETAIL'] as $spreadsheetsKey => $spreadsheets) {

                    $data['CNADETAIL'][] = [                   
                        'CNA_CONTRA' => (string) $spreadsheets->CNA_CONTRA,
                        'CNA_FILIAL' => (string) $spreadsheets->CNA_FILIAL,
                        'CNA_NUMERO' => (string) str_pad(($spreadsheets->CNA_NUMERO), 6, '0', STR_PAD_LEFT),
                        'CNA_DTINI'  => (string) $spreadsheets->CNA_DTINI,
                        'CNA_DTFIM'  => (string) $spreadsheets->CNA_DTFIM,
                        'CNA_CLIENT' => (string) str_replace(['/', '.', '-'], "", $spreadsheets->CNA_CLIENT),
                        'CNA_LOJACL' => (string) $spreadsheets->CNA_LOJACL,
                        'CNA_TIPPLA' => (string) str_pad(($spreadsheets->CNA_TIPPLA),3, '0', STR_PAD_LEFT),
                        'CNA_FLREAJ' => (string) $spreadsheets->CNA_FLREAJ
                    ];

                    $data['CNBDETAIL'] = [];
                    
                    foreach ($properties['CNBDETAIL'] as $spreadsheetsRowsKey => $spreadsheetsRows) {
                        
                        if (isset($spreadsheetsRows->CNB_ITEM)) {
                            $data['CNBDETAIL'][] = [
                                'CNB_ITEM'   => (string) str_pad(($spreadsheetsRows->CNB_ITEM), 3, '0', STR_PAD_LEFT),
                                'CNB_NUMERO' => (string) str_pad(($spreadsheets->CNA_NUMERO), 6, '0', STR_PAD_LEFT),
                                'CNB_PRODUT' => $spreadsheetsRows->CNB_PRODUT,
                                'CNB_QUANT'  => (string) $spreadsheetsRows->CNB_QUANT,
                                'CNB_VLUNIT' => (string) $spreadsheetsRows->CNB_VLUNIT,
                                'CNB_PEDTIT' => (string) $spreadsheetsRows->CNB_PEDTIT,
                                'CNB_CC'     => (string) $spreadsheetsRows->CNB_CC,
                                'CNB_ITEMCT' => $spreadsheetsRows->CNB_ITEMCT
                            ];
                        }
                    }
                }

                $response = Http::withBasicAuth(env('WS_PROTHEUS_USERNAME', 'admin'), env('WS_PROTHEUS_PASSWORD', 'q1w2e3r4_t5'))
                ->post(env('WS_PROTHEUS_URL')."/contratos", $data);

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