<?php

namespace App\Console\Commands\Sgt;

use Illuminate\Console\Command;
use App\Model\Schedules;
use App\Model\SchedulesLog;
use App\Model\Integration_queue;
use Illuminate\Support\Facades\Http;
use App\Traits\AlertMail;
use DateTime;
use DB;
use Str;

class SgtDinamicsChargeContractsQueue extends Command
{

    use AlertMail;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Integration:SgtDinamicsChargeContractsQueue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotina para colocar os contratos do Sgt na fila de integração com o dynamics';

    /**
     * The console command variables custom.
     *
     * @var string
     */
    protected $origin_model    = '';
    protected $title           = 'SGT - Carga dos Contratos Para o Dynamics';
    protected $origin          = 'SGT - Contratos';
    protected $origin_command  = null;
    protected $destiny_model   = null;
    protected $destiny         = 'Dynamics - Contratos';
    protected $destiny_command = 'Integration:SgtDinamicsProcessingContractsQueue';
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

        $curretDateTime = new DateTime();
        $endDate        = $curretDateTime->format('Y-m-d\TH:i:s');
		
		$lastExecSchedule = SchedulesLog::where('id_schedule', $schedule->id)
            ->first();
		
		if(empty($lastExecSchedule)) {
			$lastExecSchedule = SchedulesLog::create(array(
                'id_schedule' => $schedule->id,
                'last_exec'   => $endDate,
				'command' => $schedule->command
            ));
		}
		
		$startDate = $lastExecSchedule->last_exec;

        $response = Http::withToken(env('WS_SGT_API_TOKEN'))
            ->withOptions([
                "verify" => false
            ])
            ->acceptJson()
            ->get(env('WS_SGT_API_ENDPOINT') . "/atendimentos?deDataModificacao=" . urlencode($startDate) . "&ateDataModificacao=" . urlencode($endDate));

        if (!$response->ok()) {
            $error = 'Erro ao executar a schedule ' . $this->title . '.';
            $this->sendAlert($schedule->email, $error, $this->title);
            return $this->info($error);
        }

        $response  = $response->json();
        $contracts = [];
        $totalRows = 0;

        if (!empty($response['Atendimentos'])) {

            if (isset($response['Atendimentos']['Atendimento']['idAtendimento'])) {
                $contracts[] = $response['Atendimentos']['Atendimento'];
            } else {
                $contracts = $response['Atendimentos']['Atendimento'];
            }

            foreach ($contracts as $key => $value) {
				
				// Integra os contratos com o status "emexecucao"
				if($value['atendimentoStatus'] == 'emexecucao') {
					$value['atendimentoStatus'] = 'aceito';
				}

                if ($value['atendimentoStatus'] == 'aceito' || $value['atendimentoStatus'] == 'cancelado') {

                    $CN9_DTINIC = date('Y-m-d', strtotime(substr($value['dataAceitacao'], 0, 10)));
                    $CN9_DTFIM  = date('Y-m-d', strtotime("+12 months", strtotime(substr($value['dataAceitacao'], 0, 10))));
                    
                    //Dados Gerais
                    $data['CN9MASTER'] = [
                        "CN9_FILIAL"         => $value['unidade'],
                        "CN9_DTINIC"         => $CN9_DTINIC,
                        "CN9_DTFIM"          => $CN9_DTFIM,
                        "CN9_NATURE"         => "4010140002",
                        "CN9_NUMERO"         => "SGT-" . $value['idProposta'],
                        "CN9_UNVIGE"         => "2", //1=Dias;2=Meses;3=Anos;4=Indeterminada
                        "CN9_VIGE"           => "12",
                        "CN9_MOEDA"          => "01",
                        "CN9_CONDPG"         => "001",
                        "CN9_TPCTO"          => "022",
                        "CN9_FLGRJ"          => "1",
                        "CN9_FLGCAU"         => "1",
                        "CN9_ASSINA"         => $CN9_DTINIC,
                        "CN9_SITUAC"         => "05",
                        "CN9_TOTAL_PARCELA"  => "",
                        "CN9_COMP_INICIAL"   => "",
                        "CN9_INICIO_MEDICAO" => "",
                        "CN9_PERIODICADE"    => "",
                        "CN9_AVANCA_PARCELA" => "",
                        "CN9_ULTIMO_DIA_MES" => "",
                        "CN9_VALORCONT"      => 0,
                        "CN9_OBJCTO"         => $value['titulo'],
                        "ACAO"               => $value['atendimentoStatus']
                    ];

                    //Cliente
                    $data["CNCDETAIL"] = [];
                    $data["CNCDETAIL"][] = [
                        "CNC_CODIGO" => "",
                        "CNC_LOJA"   => "",
                        "CPF_CNPJ"   => $value['cliente']
                    ];

                    //Planilha do contrato
                    $data['CNADETAIL'] = [];
                    $data["CNADETAIL"][] = [
                        "CNA_FILIAL" => $value['unidade'],
                        "CNA_CONTRA" => $value['idProposta'],
                        "CNA_NUMERO" => "000001",
                        "CNA_CLIENT" => $value['cliente'],
                        "CNA_LOJACL" => "",
                        "CNA_TIPPLA" => "023",
                        "CNA_FLREAJ" => "1",
                        "CNA_DTINI"  => $CN9_DTINIC,
                        "CNA_DTFIM"  => $CN9_DTFIM
                    ];

                    //Itens planilha do contrato
                    $data["CNBDETAIL"] = [];
                    $data["CNBDETAIL"][] = [
                        "CNB_ITEM"   => str_pad(('001'), 3, '0', STR_PAD_LEFT),
                        "CNB_NUMERO" => str_pad(('000001'), 6, '0', STR_PAD_LEFT),
                        "CNB_PRODUT" => $value['idProdutoRegional'],
                        "CNB_QUANT"  => (int) $value['numeroDeProducaoEstimada'],
                        "CNB_VLUNIT" => ($value['numeroDeProducaoEstimada'] > 0 && $value['numeroDeReceitaEstimada']) ? ($value['numeroDeReceitaEstimada'] / (int) $value['numeroDeProducaoEstimada']) : (int) $value['numeroDeReceitaEstimada'],
                        "CNB_PEDTIT" => "2"
                    ];

                    foreach ($data["CNBDETAIL"] as $key => $row) {
                        $data['CN9MASTER']['CN9_VALORCONT'] += ($row['CNB_QUANT'] * $row['CNB_VLUNIT']);
                    }

                    $integration_queue = Integration_queue::create(array(
                        'origin_model'    => $this->origin_model,
                        'origin_key'      => $value['idProposta'],
                        'origin'          => $this->origin,
                        'origin_command'  => $this->signature,
                        'destiny_model'   => $this->destiny_model,
                        'destiny_key'     => $value['idProposta'],
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

        return $this->info('A Rotina ' . $this->title . ' foi executada com sucesso, foi colocado na fila  ' . $totalRows . ' registros para integração.');
    }
}
