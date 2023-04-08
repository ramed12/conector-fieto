	<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes Autenticação
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['namespace' => 'Auth'], function() {

	Route::get('auth', 												array('as' => 'auth', 								'uses' 	=> 'LoginController@index', 				'nickname' => "Login"));	
	Route::post('auth', 											array('as' => 'auth', 								'uses' 	=> 'LoginController@authenticate', 			'nickname' => "Login"));
	Route::get('auth/logout', 										array('as' => 'auth-logout', 						'uses' 	=> 'LoginController@logout', 				'nickname' => "Logout"));

	Route::get('auth/esqueci-minha-senha', 							array('as'	=> 'auth-i-forgot-my-password', 		'uses' => 'ForgotPasswordController@index',  		'nickname' => "Esqueci Minha Senha"));
	Route::post('auth/esqueci-minha-senha', 						array('as'  => 'auth-i-forgot-my-password', 		'uses' => 'ForgotPasswordController@update', 		'nickname' => "Esqueci Minha Senha"));
		
	Route::get('auth/alterar-senha', 								array('as'	=> 'auth-reset-password', 				'uses' => 'ResetPasswordController@index',  		'nickname' => "Redefinir Senha"));
	Route::post('auth/alterar-senha', 								array('as'	=> 'auth-reset-password', 				'uses' => 'ResetPasswordController@update', 		'nickname' => "Redefinir Senha"));

});

/*
|--------------------------------------------------------------------------
| CMS Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::group(['namespace' => 'Cms', 'middleware' => 'auth:cms'], function() {
	Route::get('/',												array('as' => 'cms-home',							'uses' 	=> 'HomeController@index', 							'resource' => 'Home', 				'nickname' => 'Home', 						'activemenu' => true));

	Route::get('meus-dados', 									array('as' => 'cms-my-data', 	    				'uses'  => 'My_dataController@index', 						'resource' => 'Meus Dados',  		'nickname' => "Meus Dados", 				'activemenu' => true));
	Route::put('meus-dados', 									array('as' => 'cms-my-data', 	    				'uses'  => 'My_dataController@store', 						'resource' => 'Meus Dados',   		'nickname' => "Meus Dados", 				'activemenu' => false));

	Route::group(['prefix' => 'logs'], function() {
		
		Route::get('logs-de-erros',						array('as' => 'cms-logs-erros', 	    			'uses' 	=> 'ErrosController@index', 			'resource' => 'Logs de Erros',		'nickname' => "Logs de Erros", 		'activemenu' => true));
		Route::get('logs-de-erros/{file}',				array('as' => 'cms-logs-erros-downloads', 	    	'uses' 	=> 'ErrosController@show', 				'resource' => 'Logs de Erros',		'nickname' => "Logs de Erros", 		'activemenu' => false));
		Route::get('logs-de-erros/exluir/{file}',		array('as' => 'cms-logs-erros-downloads-delete', 	'uses'  => 'ErrosController@delete', 			'resource' => 'Logs de Erros',		'nickname' => "Logs de Erros", 		'activemenu' => false));		
	
		Route::get('logs-de-integracoes', 				array('as'	=> 'cms-integration-queue', 			'uses' => 'Integration_queueController@index', 					'resource' => "Logs de Integrações",			'nickname' => "Logs de Integrações", 		 	'menu_control' => true));
		Route::get('logs-de-integracoes/{id}', 			array('as'  => 'cms-integration-queue-show', 		'uses' => 'Integration_queueController@show', 					'resource' => "Logs de Integrações",			'nickname' => "Logs de Integrações", 			'menu_control' => false));
		Route::get('logs-de-integracoes/excluir/{id}', 	array('as'  => 'cms-integration-queue-delete', 		'uses' => 'Integration_queueController@delete', 				'resource' => "Logs de Integrações",			'nickname' => "Logs de Integrações", 			'menu_control' => false));
		Route::get('logs-de-integracoes/queue/{id}', 	array('as'  => 'cms-integration-set-to-queue', 		'uses' => 'Integration_queueController@setToQueue', 				'resource' => "Logs de Integrações",			'nickname' => "Logs de Integrações", 			'menu_control' => false));

	});

	Route::group(['prefix' => 'configuracoes'], function() {
		
		Route::get('usuarios', 														array('as'	=> 'cms-users', 						'uses' => 'UsersController@index', 							'resource' => "Usuários",			'nickname' => "Usuários", 		 	'menu_control' => true));
		Route::get('usuarios/adicionar', 											array('as'  => 'cms-users-create', 					'uses' => 'UsersController@create', 						'resource' => "Usuários",			'nickname' => "Usuários", 			'menu_control' => false));
		Route::post('usuarios/adicionar', 											array('as'  => 'cms-users-create', 					'uses' => 'UsersController@store', 							'resource' => "Usuários",			'nickname' => "Usuários", 			'menu_control' => false));
		Route::get('usuarios/{id}', 												array('as'  => 'cms-users-show', 					'uses' => 'UsersController@show', 							'resource' => "Usuários",			'nickname' => "Usuários", 			'menu_control' => false));
		Route::put('usuarios/{id}', 												array('as'  => 'cms-users-update',					'uses' => 'UsersController@update', 						'resource' => "Usuários",			'nickname' => "Usuários", 			'menu_control' => false));
		Route::get('usuarios/excluir/{id}', 										array('as'  => 'cms-users-delete', 					'uses' => 'UsersController@destroy', 						'resource' => "Usuários",			'nickname' => "Usuários", 			'menu_control' => false));

		Route::get('schedules-de-integracoes', 										array('as'	=> 'cms-schedules', 					'uses' => 'SchedulesController@index', 					'resource' => "Schedules de Integrações",			'nickname' => "Schedules de Integrações", 		 	'menu_control' => true));
		Route::get('schedules-de-integracoes/{id}', 								array('as'  => 'cms-schedules-show', 				'uses' => 'SchedulesController@show', 					'resource' => "Schedules de Integrações",			'nickname' => "Schedules de Integrações", 			'menu_control' => false));
		Route::put('schedules-de-integracoes/{id}', 								array('as'  => 'cms-schedules-update',				'uses' => 'SchedulesController@update', 				'resource' => "Schedules de Integrações",			'nickname' => "Schedules de Integrações", 			'menu_control' => false));
		Route::get('schedules-de-integracoes/excluir/{id}', 						array('as'  => 'cms-schedules-delete', 				'uses' => 'SchedulesController@destroy', 				'resource' => "Schedules de Integrações",			'nickname' => "Schedules de Integrações", 			'menu_control' => false));

		Route::get('tabela-de-para', 												array('as'	=> 'cms-from-to', 						'uses' => 'From_toController@index', 						'resource' => "Tabela De/Para",			'nickname' => "Tabela De/Para", 		 	'menu_control' => true));
		Route::get('tabela-de-para/adicionar', 										array('as'  => 'cms-from-to-create', 				'uses' => 'From_toController@create', 						'resource' => "Tabela De/Para",			'nickname' => "Tabela De/Para", 			'menu_control' => false));
		Route::post('tabela-de-para/adicionar', 									array('as'  => 'cms-from-to-create', 				'uses' => 'From_toController@store', 						'resource' => "Tabela De/Para",			'nickname' => "Tabela De/Para", 			'menu_control' => false));
		Route::get('tabela-de-para/{id}', 											array('as'  => 'cms-from-to-show', 					'uses' => 'From_toController@show', 						'resource' => "Tabela De/Para",			'nickname' => "Tabela De/Para", 			'menu_control' => false));
		Route::put('tabela-de-para/{id}', 											array('as'  => 'cms-from-to-update',				'uses' => 'From_toController@update', 						'resource' => "Tabela De/Para",			'nickname' => "Tabela De/Para", 			'menu_control' => false));
		Route::get('tabela-de-para/excluir/{id}', 									array('as'  => 'cms-from-to-delete', 				'uses' => 'From_toController@destroy', 						'resource' => "Tabela De/Para",			'nickname' => "Tabela De/Para", 			'menu_control' => false));

	});	

	Route::group(['prefix' => 'integracoes'], function() {
		
		Route::get('sgt-carga-clientes/{command}', 		array('as' => 'Integration:SgtDinamicsChargeClientsQueue', function($command){
			Artisan::call(base64_decode($command));
			Request::session()->flash('alert', array('code'=> 'success', 'text'  => Artisan::output()));
			return redirect(route('cms-integration-queue'));
		}));

		Route::get('sgt-processamento-clientes/{command}', 		array('as' => 'Integration:SgtDinamicsProcessingClientsQueue', function($command){
			Artisan::call(base64_decode($command));
			Request::session()->flash('alert', array('code'=> 'success', 'text'  => Artisan::output()));
			return redirect(route('cms-integration-queue'));
		}));

		Route::get('sgt-carga-produtos/{command}', 		array('as' => 'Integration:SgtDinamicsChargeProductsQueue', function($command){
			Artisan::call(base64_decode($command));
			Request::session()->flash('alert', array('code'=> 'success', 'text'  => Artisan::output()));
			return redirect(route('cms-integration-queue'));
		}));

		Route::get('sgt-processamento-produtos/{command}', 		array('as' => 'Integration:SgtDinamicsProcessingProductsQueue', function($command){
			Artisan::call(base64_decode($command));
			Request::session()->flash('alert', array('code'=> 'success', 'text'  => Artisan::output()));
			return redirect(route('cms-integration-queue'));
		}));
		
		Route::get('sgt-carga-oportunidades/{command}', 		array('as' => 'Integration:SgtDinamicsChargeOpportunitiesQueue', function($command){
			Artisan::call(base64_decode($command));
			Request::session()->flash('alert', array('code'=> 'success', 'text'  => Artisan::output()));
			return redirect(route('cms-integration-queue'));
		}));
		
		Route::get('sgt-processamento-oportunidades/{command}', 		array('as' => 'Integration:SgtDinamicsProcessingOpportunitiesQueue', function($command){
			Artisan::call(base64_decode($command));
			Request::session()->flash('alert', array('code'=> 'success', 'text'  => Artisan::output()));
			return redirect(route('cms-integration-queue'));
		}));

		Route::get('sgt-carga-contratos/{command}', 		array('as' => 'Integration:SgtDinamicsChargeContractsQueue', function($command){
			Artisan::call(base64_decode($command));
			Request::session()->flash('alert', array('code'=> 'success', 'text'  => Artisan::output()));
			return redirect(route('cms-integration-queue'));
		}));

		Route::get('sgt-processamento-contratos/{command}', 		array('as' => 'Integration:SgtDinamicsProcessingContractsQueue', function($command){
			Artisan::call(base64_decode($command));
			Request::session()->flash('alert', array('code'=> 'success', 'text'  => Artisan::output()));
			return redirect(route('cms-integration-queue'));
		}));

		Route::get('sgt-carga-medicoes/{command}', 		array('as' => 'Integration:SgtDinamicsChargeMeasurementsQueue', function($command){
			Artisan::call(base64_decode($command));
			Request::session()->flash('alert', array('code'=> 'success', 'text'  => Artisan::output()));
			return redirect(route('cms-integration-queue'));
		}));

		Route::get('sgt-processamento-medicoes/{command}', 		array('as' => 'Integration:SgtDinamicsProcessingMeasurementsQueue', function($command){
			Artisan::call(base64_decode($command));
			Request::session()->flash('alert', array('code'=> 'success', 'text'  => Artisan::output()));
			return redirect(route('cms-integration-queue'));
		}));


		Route::get('orquestra-carga-clientes/{command}', 		array('as' => 'Integration:OrquestraDinamicsChargeClientsQueue', function($command){
			Artisan::call(base64_decode($command));
			Request::session()->flash('alert', array('code'=> 'success', 'text'  => Artisan::output()));
			return redirect(route('cms-integration-queue'));
		}));

		Route::get('orquestra-processamento-clientes/{command}', 		array('as' => 'Integration:OrquestraDinamicsProcessingClientsQueue', function($command){
			Artisan::call(base64_decode($command));
			Request::session()->flash('alert', array('code'=> 'success', 'text'  => Artisan::output()));
			return redirect(route('cms-integration-queue'));
		}));

		Route::get('orquestra-carga-contratos/{command}', 		array('as' => 'Integration:OrquestraDinamicsChargeContractsQueue', function($command){
			Artisan::call(base64_decode($command));
			Request::session()->flash('alert', array('code'=> 'success', 'text'  => Artisan::output()));
			return redirect(route('cms-integration-queue'));
		}));

		Route::get('orquestra-processamento-contratos/{command}', 		array('as' => 'Integration:OrquestraDinamicsProcessingContractsQueue', function($command){
			Artisan::call(base64_decode($command));
			Request::session()->flash('alert', array('code'=> 'success', 'text'  => Artisan::output()));
			return redirect(route('cms-integration-queue'));
		}));

		Route::get('orquestra-carga-medicoes/{command}', 		array('as' => 'Integration:OrquestraDinamicsChargeMeasurementsQueue', function($command){
			Artisan::call(base64_decode($command));
			Request::session()->flash('alert', array('code'=> 'success', 'text'  => Artisan::output()));
			return redirect(route('cms-integration-queue'));
		}));

		Route::get('orquestra-processamento-medicoes/{command}', 		array('as' => 'Integration:OrquestraDinamicsProcessingMeasurementsQueue', function($command){
			Artisan::call(base64_decode($command));
			Request::session()->flash('alert', array('code'=> 'success', 'text'  => Artisan::output()));
			return redirect(route('cms-integration-queue'));
		}));

		Route::get('dynamics-carga-clientes/{command}', 		array('as' => 'Integration:DynamicsProtheusChargeClientsQueue', function($command){
			Artisan::call(base64_decode($command));
			Request::session()->flash('alert', array('code'=> 'success', 'text'  => Artisan::output()));
			return redirect(route('cms-integration-queue'));
		}));

		Route::get('dynamics-processamento-clientes/{command}', 		array('as' => 'Integration:DynamicsProtheusProcessingClientsQueue', function($command){
			Artisan::call(base64_decode($command));
			Request::session()->flash('alert', array('code'=> 'success', 'text'  => Artisan::output()));
			return redirect(route('cms-integration-queue'));
		}));

		Route::get('dynamics-carga-contratos/{command}', 		array('as' => 'Integration:DynamicsProtheusChargeContractsQueue', function($command){
			Artisan::call(base64_decode($command));
			Request::session()->flash('alert', array('code'=> 'success', 'text'  => Artisan::output()));
			return redirect(route('cms-integration-queue'));
		}));

		Route::get('dynamics-processamento-contratos/{command}', 		array('as' => 'Integration:DynamicsProtheusProcessingContractsQueue', function($command){
			Artisan::call(base64_decode($command));
			Request::session()->flash('alert', array('code'=> 'success', 'text'  => Artisan::output()));
			return redirect(route('cms-integration-queue'));
		}));

		Route::get('dynamics-carga-medicoes/{command}', 		array('as' => 'Integration:DynamicsProtheusChargeMeasurementsQueue', function($command){
			Artisan::call(base64_decode($command));
			Request::session()->flash('alert', array('code'=> 'success', 'text'  => Artisan::output()));
			return redirect(route('cms-integration-queue'));
		}));

		Route::get('dynamics-processamento-medicoes/{command}', 		array('as' => 'Integration:DynamicsProtheusProcessingMeasurementsQueue', function($command){
			Artisan::call(base64_decode($command));
			Request::session()->flash('alert', array('code'=> 'success', 'text'  => Artisan::output()));
			return redirect(route('cms-integration-queue'));
		}));


		Route::get('protheus-processamento-contas-a-receber/{command}', 		array('as' => 'Integration:ProtheusDynamicsProcessingBillsToReceiveQueue', function($command){
			Artisan::call(base64_decode($command));
			Request::session()->flash('alert', array('code'=> 'success', 'text'  => Artisan::output()));
			return redirect(route('cms-integration-queue'));
		}));	

	});

	Route::group(['prefix' => 'manutencao'], function() {

		Route::get('atualizacao-de-banco-de-dados', array('as' => 'cms-database-update', function(){
			
			Artisan::call('migrate', [
                    '--path'      => 'database/migrations',
                    '--force'     => true,
                    '--database'  => env('DB_CONNECTION')
                ]
            );

            Request::session()->flash('alert', array('code'=> 'success', 'text'  => Artisan::output()));

            Artisan::call('db:seed', [
                    '--class'     => 'DatabaseSeeder',
                    '--force'     => true,
                    '--database'  => env('DB_CONNECTION')
                ]
            );
			
			return redirect(route('cms-home'));
		}));	
	});
});