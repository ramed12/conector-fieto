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

class SgtDinamicsChargeOpportunitiesQueue extends Command
{

    use AlertMail;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Integration:SgtDinamicsChargeOpportunitiesQueue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotina para colocar as oportunidades do Sgt na fila de integração com o dynamics';

    /**
     * The console command variables custom.
     *
     * @var string
     */
    protected $origin_model    = '';
    protected $title           = 'SGT - Carga das Oportunidades Para o Dynamics';
    protected $origin          = 'SGT - Oportunidades';
    protected $origin_command  = null;
    protected $destiny_model   = null;
    protected $destiny         = 'Dynamics - Oportunidades';
    protected $destiny_command = 'Integration:SgtDinamicsProcessingOpportunitiesQueue';
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
        $opportunities = [];
        $totalRows = 0;

        if (!empty($response['Atendimentos'])) {

            if (isset($response['Atendimentos']['Atendimento']['idAtendimento'])) {
                $opportunities[] = $response['Atendimentos']['Atendimento'];
            } else {
                $opportunities = $response['Atendimentos']['Atendimento'];
            }

            foreach ($opportunities as $key => $value) {
												
				// Integra as oportunidades que mudaram de status antes de serem carregadas pela ferramenta
				if($value['atendimentoStatus'] == 'emexecucao') {
					$value['atendimentoStatus'] = 'aceito';
				}
												
                if ($value['atendimentoStatus'] == 'negociacao' || $value['atendimentoStatus'] == 'aceito' || $value['atendimentoStatus'] == 'recusado') {					
					
                    $data = [];

                    $data['idAtendimento']              = $value['idAtendimento'];
                    $data['atendimentoStatus']          = $value['atendimentoStatus'];
                    $data['dataInicioPrevista']         = $value['dataInicioPrevista'];                    
                    $data['dataConclusaoPrevista']      = $value['dataConclusaoPrevista'];
                    $data['idProposta']                 = $value['idProposta'];
                    $data['descricaoProposta']          = $value['descricaoProposta'];
                    $data['titulo']                     = $value['titulo'];
                    $data['unidade']                    = $value['unidade'];
                    $data['idProdutoRegional']          = $value['idProdutoRegional'];
                    $data['dataNegociacao']             = $value['dataNegociacao'];
                    $data['dataAceitacao']              = $value['dataAceitacao'];
                    $data['dataRecusa']                 = $value['dataRecusa'];
                    $data['numeroDeProducaoEstimada']   = $value['numeroDeProducaoEstimada'];
                    $data['numeroDeReceitaEstimada']    = $value['numeroDeReceitaEstimada'];
                    $data['cliente']                    = $value['cliente'];

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
