<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\Sgt\SgtDinamicsChargeClientsQueue::class,
        Commands\Sgt\SgtDinamicsProcessingClientsQueue::class,  
        Commands\Sgt\SgtDinamicsChargeProductsQueue::class,
        Commands\Sgt\SgtDinamicsProcessingProductsQueue::class,
        Commands\Sgt\SgtDinamicsChargeContractsQueue::class,
        Commands\Sgt\SgtDinamicsProcessingContractsQueue::class, 
        Commands\Sgt\SgtDinamicsChargeMeasurementsQueue::class,
        Commands\Sgt\SgtDinamicsProcessingMeasurementsQueue::class,

        Commands\Orquestra\OrquestraDinamicsChargeClientsQueue::class,
        Commands\Orquestra\OrquestraDinamicsProcessingClientsQueue::class, 
        Commands\Orquestra\OrquestraDinamicsChargeMeasurementsQueue::class,
        Commands\Orquestra\OrquestraDinamicsProcessingMeasurementsQueue::class,

        Commands\Dynamics\DynamicsProtheusChargeClientsQueue::class,
        Commands\Dynamics\DynamicsProtheusProcessingClientsQueue::class,
        Commands\Dynamics\DynamicsProtheusChargeContractsQueue::class,
        Commands\Dynamics\DynamicsProtheusProcessingContractsQueue::class,
        Commands\Dynamics\DynamicsProtheusChargeMeasurementsQueue::class,
        Commands\Dynamics\DynamicsProtheusProcessingMeasurementsQueue::class,

        Commands\Protheus\ProtheusDynamicsProcessingBillsToReceiveQueue::class, 
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //SGT
        $schedule->command('Integration:SgtDinamicsChargeClientsQueue')->everyMinute();
        $schedule->command('Integration:SgtDinamicsProcessingClientsQueue')->everyMinute();
        $schedule->command('Integration:SgtDinamicsChargeProductsQueue')->everyMinute();
        $schedule->command('Integration:SgtDinamicsProcessingProductsQueue')->everyMinute();
        $schedule->command('Integration:SgtDinamicsChargeMeasurementsQueue')->everyMinute();
        $schedule->command('Integration:SgtDinamicsProcessingMeasurementsQueue')->everyMinute();

        //Orquestra
        $schedule->command('Integration:OrquestraDinamicsChargeClientsQueue')->everyMinute();
        $schedule->command('Integration:OrquestraDinamicsProcessingClientsQueue')->everyMinute();
        $schedule->command('Integration:OrquestraDinamicsChargeMeasurementsQueue')->everyMinute(); 
        $schedule->command('Integration:OrquestraDinamicsProcessingMeasurementsQueue')->everyMinute();              
        
        //Dynamics
        $schedule->command('Integration:DynamicsProtheusChargeClientsQueue')->everyMinute();
        $schedule->command('Integration:DynamicsProtheusProcessingClientsQueue')->everyMinute();
        $schedule->command('Integration:DynamicsProtheusChargeContractsQueue')->everyMinute();
        $schedule->command('Integration:DynamicsProtheusProcessingContractsQueue')->everyMinute();
        $schedule->command('Integration:DynamicsProtheusChargeMeasurementsQueue')->everyMinute();
        $schedule->command('Integration:DynamicsProtheusProcessingMeasurementsQueue')->everyMinute();

        //Protheus
        $schedule->command('Integration:ProtheusDynamicsProcessingBillsToReceiveQueue')->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
