<?php

namespace App\Console\Commands\Sgt;

use Illuminate\Console\Command;
use App\Model\Schedules;
use App\Model\SchedulesLog;
use App\Model\Integration_queue;
use Illuminate\Support\Facades\Http;
use App\Traits\DynamicsApi;
use App\Traits\AlertMail;
use DB;
use Str;
use DateTime;

class SgtDinamicsChargeProductsQueue extends Command
{
    use DynamicsApi, AlertMail;

    /**
     * The name and signature of the console command.
     *
     * @var string
    */
    protected $signature = 'Integration:SgtDinamicsChargeProductsQueue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotina para colocar os produtos do Sgt na fila de integração com o dynamics';

    /**
     * The console command variables custom.
     *
     * @var string
    */
    protected $origin_model    = '';
    protected $title           = 'SGT - Carga dos Produtos Para o Dynamics';
    protected $origin          = 'SGT - Produtos';
    protected $origin_command  = null;
    protected $destiny_model   = null;
    protected $destiny         = 'Dynamics - Produtos';
    protected $destiny_command = 'Integration:SgtDinamicsProcessingProductsQueue';
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

        //Produtos Regionais 
        $response = Http::withToken(env('WS_SGT_API_TOKEN'))
        ->withOptions([
            "verify" => false
        ])
        ->acceptJson()
        ->get(env('WS_SGT_API_ENDPOINT')."/produtosRegionais?deDataModificacao=".urlencode($startDate)."&ateDataModificacao=".urlencode($endDate));

        if (!$response->ok()) {
            $error = 'Erro ao executar a schedule '.$this->title.'.';
            $this->sendAlert($schedule->email, $error, $this->title);
            return $this->info($error);
        }        

        $produtosRegionais = $response->json();

        $totalRows         = 0; 

        if (!empty($produtosRegionais['ProdutosRegionais'])) {

            if (isset($produtosRegionais['ProdutosRegionais']['ProdutoRegional']['idProdutoRegional'])) {

                $data = [
                    "B1_COD"     => $produtosRegionais['ProdutosRegionais']['ProdutoRegional']["idProdutoRegional"],
                    "B1_DESC"    => $produtosRegionais['ProdutosRegionais']['ProdutoRegional']["nome"],
                    "B1_TIPO"    => "",
                    "B1_UM"      => "",
                    "B1_LOCPAD"  => "",
                    "B1_CC"      => $produtosRegionais['ProdutosRegionais']['ProdutoRegional']["CRProdutoRegional"],
                    "B1_PCDN"    => $produtosRegionais['ProdutosRegionais']['ProdutoRegional']["codigoDNProdutoCategoria"],
                    "B1_XCODORI" => "SGT",
                    "B1_XSISORI" => $produtosRegionais['ProdutosRegionais']['ProdutoRegional']["idProdutoRegional"]
                ];

                $integration_queue = Integration_queue::create(array(
                    'origin_model'    => $this->origin_model,
                    'origin_key'      => $produtosRegionais['ProdutosRegionais']['ProdutoRegional']['idProdutoRegional'],
                    'origin'          => $this->origin,
                    'origin_command'  => $this->signature,
                    'destiny_model'   => $this->destiny_model,
                    'destiny_key'     => $produtosRegionais['ProdutosRegionais']['ProdutoRegional']['idProdutoRegional'],
                    'destiny'         => $this->destiny,
                    'destiny_command' => $this->destiny_command,
                    'properties'      => base64_encode(json_encode($data)),
                    'status'          => 2
                ));

                $totalRows++; 
                
            } else {

                foreach ($produtosRegionais['ProdutosRegionais']['ProdutoRegional']  as $key => $value) {

                    $data = [
                        "B1_COD"     => $value["idProdutoRegional"],
                        "B1_DESC"    => $value["nome"],
                        "B1_TIPO"    => "",
                        "B1_UM"      => "",
                        "B1_LOCPAD"  => "",
                        "B1_CC"      => $value["CRProdutoRegional"],
                        "B1_PCDN"    => $value["codigoDNProdutoCategoria"],
                        "B1_XCODORI" => "SGT",
                        "B1_XSISORI" => $value["idProdutoRegional"]
                    ];

                    $integration_queue = Integration_queue::create(array(
                        'origin_model'    => $this->origin_model,
                        'origin_key'      => $value['idProdutoRegional'],
                        'origin'          => $this->origin,
                        'origin_command'  => $this->signature,
                        'destiny_model'   => $this->destiny_model,
                        'destiny_key'     => $value['idProdutoRegional'],
                        'destiny'         => $this->destiny,
                        'destiny_command' => $this->destiny_command,
                        'properties'      => base64_encode(json_encode($data)),
                        'status'          => 2
                    ));

                    $totalRows++;  
                }
            }
            
        } 

        //Produtos Nacionais 
        $response = Http::withToken(env('WS_SGT_API_TOKEN'))
        ->withOptions([
            "verify" => false
        ])
        ->acceptJson()
        ->get(env('WS_SGT_API_ENDPOINT')."/produtosNacionais?deDataModificacao=".urlencode($startDate)."&ateDataModificacao=".urlencode($endDate));

        if (!$response->ok()) {
            return $this->info('Erro ao executar a schedule '.$this->title.'.');
        }        

        $produtosNacionais = $response->json(); 

        if (!empty($produtosNacionais['ProdutosNacionais'])) {
            
            if (isset($produtosNacionais['ProdutosNacionais']['ProdutoNacional']['idProdutoNacional'])) {

                $data = [
                    "B1_COD"     => $produtosNacionais['ProdutosNacionais']['ProdutoNacional']["idProdutoNacional"],
                    "B1_DESC"    => $produtosNacionais['ProdutosNacionais']['ProdutoNacional']["descricaoProdutoNacional"],
                    "B1_TIPO"    => "",
                    "B1_UM"      => "",
                    "B1_LOCPAD"  => "",
                    "B1_CC"      => $produtosNacionais['ProdutosNacionais']['ProdutoNacional']["CRProdutoNacional"],
                    "B1_PCDN"    => $produtosNacionais['ProdutosNacionais']['ProdutoNacional']["codigoDNProdutoCategoria"],
                    "B1_XCODORI" => "SGT",
                    "B1_XSISORI" => $produtosNacionais['ProdutosNacionais']['ProdutoNacional']["idProdutoNacional"]
                ];

                $integration_queue = Integration_queue::create(array(
                    'origin_model'    => $this->origin_model,
                    'origin_key'      => $produtosNacionais['ProdutosNacionais']['ProdutoNacional']['idProdutoNacional'],
                    'origin'          => $this->origin,
                    'origin_command'  => $this->signature,
                    'destiny_model'   => $this->destiny_model,
                    'destiny_key'     => $produtosNacionais['ProdutosNacionais']['ProdutoNacional']['idProdutoNacional'],
                    'destiny'         => $this->destiny,
                    'destiny_command' => $this->destiny_command,
                    'properties'      => base64_encode(json_encode($data)),
                    'status'          => 2
                ));

                $totalRows++;
                
            } else {
                
                foreach ($produtosNacionais['ProdutosNacionais']['ProdutoNacional']  as $key => $value) {
                    
                    $data = [
                        "B1_COD"     => $value["idProdutoNacional"],
                        "B1_DESC"    => $value["descricaoProdutoNacional"],
                        "B1_TIPO"    => "",
                        "B1_UM"      => "",
                        "B1_LOCPAD"  => "",
                        "B1_CC"      => $value["CRProdutoNacional"],
                        "B1_PCDN"    => $value["codigoDNProdutoCategoria"],
                        "B1_XCODORI" => "SGT",
                        "B1_XSISORI" => $value["idProdutoNacional"]
                    ];

                    $integration_queue = Integration_queue::create(array(
                        'origin_model'    => $this->origin_model,
                        'origin_key'      => @$value['idProdutoNacional'],
                        'origin'          => $this->origin,
                        'origin_command'  => $this->signature,
                        'destiny_model'   => $this->destiny_model,
                        'destiny_key'     => @$value['idProdutoNacional'],
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
		
        return $this->info('A Rotina '.$this->title.' foi executada com sucesso, foi colocado na fila  '.$totalRows.' registros para integração.');      
    }
}
