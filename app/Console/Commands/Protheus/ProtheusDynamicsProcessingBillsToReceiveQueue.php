<?php

namespace App\Console\Commands\Protheus;

use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use App\Model\Schedules;
use App\Model\Integration_queue;
use App\Model\From_to;
use App\Traits\AlertMail;
use Str;

class ProtheusDynamicsProcessingBillsToReceiveQueue extends Command
{
    use AlertMail;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Integration:ProtheusDynamicsProcessingBillsToReceiveQueue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotina para processar o contas a receber do protheus na fila de integração com o dynamics';

    /**
     * The console command variables custom.
     *
     * @var string
    */
    protected $origin_model    = '';
    protected $title           = 'Protheus - Processamento do Contas a Receber Para o Dynamics';
    protected $origin          = 'Protheus - Contas a Receber';
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

            $data = [
                "E1_FILIAL"  => (string) $properties['E1_FILIAL'],
                "E1_PREFIXO" => (string) $properties['E1_PREFIXO'],
                "E1_NUM"     => (string) $properties['E1_NUM'],
                "E1_PARCELA" => (string) $properties['E1_PARCELA'],
                "E1_CLIENTE" => (string) $properties['E1_CLIENTE'],
                "E1_LOJA"    => (string) $properties['E1_LOJA'],
                "E1_NOMCLI"  => (string) $properties['E1_NOMCLI'],
                "E1_EMISSAO" => (string) $properties['E1_EMISSAO'],
                "E1_VENCREA" => (string) $properties['E1_VENCREA'],
                "E1_VALOR"   => (string) $properties['E1_VALOR'],
                "E1_BAIXA"   => (string) $properties['E1_BAIXA'],
                "E1_MDCONTR" => (string) trim($properties['E1_MDCONTR']),
                "E1_MEDNUME" => (string) $properties['E1_MEDNUME']
            ];

            $data["BAIXASCONTASRECEBER"] = [];

            foreach ($properties['BAIXASCONTASRECEBER'] as $key => $row) {
                
                $data["BAIXASCONTASRECEBER"][] = [
                    "E5_FILIAL"  => (string) $row->E5_FILIAL,
                    "E5_NUMERO"  => (string) $row->E5_NUMERO,
                    "E5_PARCELA" => (string) $row->E5_PARCELA,
                    "E5_CLIFOR"  => (string) $row->E5_CLIFOR,
                    "E5_DATA"    => (string) $row->E5_DATA,
                    "E5_VALOR"   => (string) $row->E5_VALOR,
                    "E5_VLJUROS" => (string) $row->E5_VLJUROS,
                    "E5_VLMULTA" => (string) $row->E5_VLMULTA,
                    "E5_VLDESCO" => (string) $row->E5_VLDESCO,
                    "E5_DTDISPO" => (string) $row->E5_DTDISPO
                ];
            }

            try {

                $response = Http::withOptions([
                    "verify" => false
                ])
                ->post(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_BILLSTORECEIVE_STORE'), $data);

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
