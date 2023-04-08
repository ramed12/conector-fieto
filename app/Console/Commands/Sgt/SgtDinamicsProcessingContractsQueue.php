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

class SgtDinamicsProcessingContractsQueue extends Command
{
    use AlertMail;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Integration:SgtDinamicsProcessingContractsQueue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotina para processar os contratos do Sgt na fila de integração com o dynamics';

    /**
     * The console command variables custom.
     *
     * @var string
     */
    protected $origin_model    = '';
    protected $title           = 'SGT - Processamento dos Contratos Para o Dynamics';
    protected $origin          = 'SGT - Contratos';
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

        if (empty($schedule)) {

            $schedule = Schedules::create(array(
                'title'      => $this->title,
                'text'       => $this->description,
                'command'    => $this->signature,
                'pagination' => $this->pagination,
                'status'     => true
            ));
        }

        if ($schedule->status != 1) {
            return $this->info('O Schedule ' . $this->title . ' está inativo.');
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
                    "CN9_FILIAL"         => (string) $properties['CN9MASTER']->CN9_FILIAL,
                    "CN9_DTINIC"         => (string) $properties['CN9MASTER']->CN9_DTINIC,
                    "CN9_DTFIM"          => (string) $properties['CN9MASTER']->CN9_DTFIM,
                    "CN9_NATURE"         => (string) $properties['CN9MASTER']->CN9_NATURE,
                    "CN9_NUMERO"         => (string) $properties['CN9MASTER']->CN9_NUMERO,
                    "CN9_UNVIGE"         => (string) $properties['CN9MASTER']->CN9_UNVIGE,
                    "CN9_VIGE"           => (string) $properties['CN9MASTER']->CN9_VIGE,
                    "CN9_MOEDA"          => (string) $properties['CN9MASTER']->CN9_MOEDA,
                    "CN9_CONDPG"         => (string) $properties['CN9MASTER']->CN9_CONDPG,
                    "CN9_TPCTO"          => (string) $properties['CN9MASTER']->CN9_TPCTO,
                    "CN9_FLGRJ"          => (string) $properties['CN9MASTER']->CN9_FLGRJ,
                    "CN9_FLGCAU"         => (string) $properties['CN9MASTER']->CN9_FLGCAU,
                    "CN9_ASSINA"         => (string) $properties['CN9MASTER']->CN9_ASSINA,
                    "CN9_SITUAC"         => (string) $properties['CN9MASTER']->CN9_SITUAC,
                    "CN9_TOTAL_PARCELA"  => (string) $properties['CN9MASTER']->CN9_TOTAL_PARCELA,
                    "CN9_COMP_INICIAL"   => (string) $properties['CN9MASTER']->CN9_COMP_INICIAL,
                    "CN9_INICIO_MEDICAO" => (string) $properties['CN9MASTER']->CN9_INICIO_MEDICAO,
                    "CN9_PERIODICADE"    => (string) $properties['CN9MASTER']->CN9_PERIODICADE,
                    "CN9_AVANCA_PARCELA" => (string) $properties['CN9MASTER']->CN9_AVANCA_PARCELA,
                    "CN9_ULTIMO_DIA_MES" => (string) $properties['CN9MASTER']->CN9_ULTIMO_DIA_MES,
                    "CN9_VALORCONT"      => (string) $properties['CN9MASTER']->CN9_VALORCONT,
                    "CN9_OBJCTO"         => (string) $properties['CN9MASTER']->CN9_OBJCTO,
                    "ACAO"               => (string) $properties['CN9MASTER']->ACAO
                ];

                foreach ($properties['CNCDETAIL'] as $key => $CNCDETAIL) {

                    $data['DADOSCLIENTE'][]    = [
                        "CNC_CODIGO" => (string) $CNCDETAIL->CNC_CODIGO,
                        "CNC_LOJA"   => (string) $CNCDETAIL->CNC_LOJA,
                        "CPF_CNPJ"   => (string) $CNCDETAIL->CPF_CNPJ,
                    ];
                }

                $data['DADOSDAPLANILHA'] = [];

                foreach ($properties['CNADETAIL'] as $key => $CNADETAIL) {

                    $data['DADOSDAPLANILHA'][$key] = [
                        "CNA_FILIAL" => (string) $CNADETAIL->CNA_FILIAL,
                        "CNA_CONTRA" => (string) $CNADETAIL->CNA_CONTRA,
                        "CNA_NUMERO" => (string) $CNADETAIL->CNA_NUMERO,
                        "CNA_CLIENT" => (string) $CNADETAIL->CNA_CLIENT,
                        "CNA_LOJACL" => (string) $CNADETAIL->CNA_LOJACL,
                        "CNA_TIPPLA" => (string) $CNADETAIL->CNA_TIPPLA,
                        "CNA_FLREAJ" => (string) $CNADETAIL->CNA_FLREAJ,
                        "CNA_DTINI"  => (string) $CNADETAIL->CNA_DTINI,
                        "CNA_DTFIM"  => (string) $CNADETAIL->CNA_DTFIM
                    ];

                    foreach ($properties['CNBDETAIL'] as $key => $CNBDETAIL) {

                        if ($CNBDETAIL->CNB_NUMERO == $CNADETAIL->CNA_NUMERO) {

                            $data['DADOSDAPLANILHA'][$key]['DADOSITEMDAPLANILHA'][] = [
                                "CNB_ITEM"   => (string) $CNBDETAIL->CNB_ITEM,
                                "CNB_NUMERO" => (string) $CNBDETAIL->CNB_NUMERO,
                                "CNB_PRODUT" => (string) $CNBDETAIL->CNB_PRODUT,
                                "CNB_QUANT"  => (string) $CNBDETAIL->CNB_QUANT,
                                "CNB_VLUNIT" => (string) $CNBDETAIL->CNB_VLUNIT,
                                "CNB_PEDTIT" => (string) $CNBDETAIL->CNB_PEDTIT
                            ];
                        }
                    }
                }

                $response = Http::withOptions([
                    "verify" => false
                ])
                    ->post(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_CONTRACTS_STORE'), $data);

                if ($response->successful()) {
                    $value->status = 1;
                    $value->save();
                    $totalRows++;
                    unset($dataIds[$key]);
                } else {
                    if(trim($response->body()) == 'Contrato já existe') {
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
                }
            } catch (\Illuminate\Http\Client\Exception | \Illuminate\Http\Client\ConnectionException | \Illuminate\Database\QueryException | \Illuminate\Http\Client\RequestException | \Illuminate\Http\Client\ClientException | \Illuminate\Http\Client\ServerException | \Illuminate\Http\Client\BadResponseException | \Exception $e) {

                Integration_queue::whereIn('id', $dataIds)->update([
                    'status' => '2'
                ]);

                return $this->info($e->getMessage());
            }
        }

        return $this->info('A Rotina ' . $this->title . ' foi executada com sucesso, foi integrado  ' . $totalRows . ' registros e ' . $totalRowsErro . ' falhou.');
    }
}
