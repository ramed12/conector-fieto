<?php

namespace App\Console\Commands\Sgt;

use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use App\Model\Schedules;
use App\Model\Integration_queue;
use App\Model\From_to;
use App\Traits\AlertMail;
use App\Traits\DynamicsApi;
use Str;

class SgtDinamicsProcessingOpportunitiesQueue extends Command
{
    use AlertMail, DynamicsApi;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Integration:SgtDinamicsProcessingOpportunitiesQueue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotina para processar as oportunidades do Sgt na fila de integração com o dynamics';

    /**
     * The console command variables custom.
     *
     * @var string
     */
    protected $origin_model    = '';
    protected $title           = 'SGT - Processamento das Oportunidades Para o Dynamics';
    protected $origin          = 'SGT - Oportunidades';
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
						
            $data = array();			
			$data['Input'] = base64_decode($value->properties);		

            try {
				
				sleep(1);
				
				// Primeira requisição para integrar a oportunidade
                $response = $this->sendRequest($data);											
				$responseBody = json_decode($response->body());				
				
				// Repete +2 vezes a requisição para o caso de falha
				if (!$response->successful() || !$responseBody->Success) {	
					sleep(1);
					$response = $this->sendRequest($data);											
					$responseBody = json_decode($response->body());
				}
				
				if (!$response->successful() || !$responseBody->Success) {		
					sleep(1);
					$response = $this->sendRequest($data);											
					$responseBody = json_decode($response->body());
				}
				
                if ($response->successful() && $responseBody->Success) {										
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

        return $this->info('A Rotina ' . $this->title . ' foi executada com sucesso, foi integrado  ' . $totalRows . ' registros e ' . $totalRowsErro . ' falhou.');
    }
	
	/**
     * Execute the request.
     *
     * @return mixed
     */
	protected function sendRequest($data)
	{
		$response = Http::withToken($this->getAccessToken())
                ->withOptions([
                    "verify" => false
		])
		->post(env('WS_DYNAMICS_API_ENDPOINT_WORKFLOWS_OPPORTUNITIES_STORE'), $data);							
				
		return $response;
	}
}
