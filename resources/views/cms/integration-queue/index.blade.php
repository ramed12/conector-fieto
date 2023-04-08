@extends('layout.app')
@section('content')	
@include('cms.includes.header')
<section class="wrapper">
	<div class="container-fluid">
		<div class="row">	
			<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
				<ul class="breadcrumb">
					<li>
						<a href="{!!route('cms-home')!!}"><i class="fa fa-home" aria-hidden="true"></i> Inicio </a>
					</li>
					<li>
						<a><i class="fa fa-angle-double-right ml-1" aria-hidden="true"></i> Logs </a>
					</li>
					<li>
						<a href="{!!route('cms-integration-queue', http_build_query(Request::input()))!!}"><i class="fa fa-angle-double-right ml-1" aria-hidden="true"></i> Logs de Integrações</a>
					</li>
				</ul>
			</div>		
		</div>
		@include('layout.alerts')
		<div class="row">
			<div class="col-12">
		        <div class="card mb-4">       
		            <div class="card-body">		                
				        {!! Form::model(Request::input(), ['method' => 'get', 'autocomplete' => 'on', 'route' => ['cms-integration-queue', http_build_query(Request::input())], 'class' => 'my-2']) !!}      		    
							<div class="row">
								<div class="col-12 col-lg-6 col-md-6 col-sm-6">
									<div class="form-group">                    
				                       {!!Form::label('filter_search', 'Busca:')!!}
			            			   {!!Form::text('filter_search', null, ['class' => 'form-control']) !!}				            			   
			            			   @if (!empty($errors->first('filter_search')))
		            			 		<label class="error">{!!$errors->first('filter_search')!!}</label>   
		            			 	   @endif 
				                    </div>
								</div>
								<div class="col-12 col-lg-6 col-md-6 col-sm-6">
									<div class="form-group">                    
				                       {!!Form::label('filter_status', 'Status:')!!}
			            			   {!!Form::select('filter_status',['1' => 'Integrado', '2' => 'Não Integrado', '3' => 'Falha No Processamento'],  null, ['placeholder' => 'Selecione', 'class' => 'form-control']) !!}				            			   
			            			   @if (!empty($errors->first('filter_status')))
		            			 		<label class="error">{!!$errors->first('filter_status')!!}</label>   
		            			 	   @endif 
				                    </div>
								</div>
							</div>
							<div class="row">
								<div class="col-12 col-lg-4 col-md-4 col-sm-4">
									<div class="form-group">                    
				                       {!!Form::label('filter_start_date', 'Data Inicial:')!!}
			            			   {!!Form::date('filter_start_date', null, ['class' => 'form-control']) !!}				            			   
			            			   @if (!empty($errors->first('filter_start_date')))
		            			 		<label class="error">{!!$errors->first('filter_start_date')!!}</label>   
		            			 	   @endif 
				                    </div>
								</div>
								<div class="col-12 col-lg-4 col-md-4 col-sm-4">
									<div class="form-group">                    
				                       {!!Form::label('filter_end_date', 'Data Final:')!!}
			            			   {!!Form::date('filter_end_date', null, ['class' => 'form-control']) !!}				            			   
			            			   @if (!empty($errors->first('filter_end_date')))
		            			 		<label class="error">{!!$errors->first('filter_end_date')!!}</label>   
		            			 	   @endif 
				                    </div>
								</div>
								<div class="col-12 col-lg-4 col-md-4 col-sm-4">
									<div class="form-group">                    
				                       {!!Form::label('filter_schedule', 'Rotina:')!!}
			            			   {!!Form::select('filter_schedule', $schedule,  null, ['placeholder' => 'Selecione', 'class' => 'form-control']) !!}				            			   
			            			   @if (!empty($errors->first('filter_schedule')))
		            			 		<label class="error">{!!$errors->first('filter_schedule')!!}</label>   
		            			 	   @endif 
				                    </div>
								</div>
							</div>
							<div class="row">
								<div class="col-12">
									<button type="submit" class="btn btn-success float-right ml-1">Pesquisar</button>
                					<a href="{!!route('cms-home')!!}" class="btn btn-secondary float-right ml-1" title="voltar">voltar</a>
								</div>
							</div>		
				      	{!! Form::close() !!}
		            </div> 
		        </div>  
			</div>
		</div>
		<div class="row">
			<div class="col-12">
				<div class="card">
					<div class="card-header bg-primary">Logs de Integrações</div>
					<div class="card-body">
						<table class="table">
							<thead>
								<tr>
									<th>Código</th>
									<th>Cd. Origem</th>
									<th>Origem</th>
									<th>Destino</th>
									<th>Data</th>
									<th>Hora</th>
									<th>Status</th>
									<th>Ações</th>
								</tr>
							</thead>
							<tbody>
								@foreach ($integration_queue as $value)
									<tr>
										<td class="align-middle" data-label="Código">{!!$value->id!!}</td>
										<td class="align-middle" data-label="Cd. Origem">{!!$value->origin_key!!}</td>
										<td class="align-middle" data-label="Origem">{!!$value->origin!!}</td>										
										<td class="align-middle" data-label="Destino">{!!$value->destiny!!}</td>
										<td class="align-middle" data-label="Data">{!!date('d-m-Y', strtotime($value->created_at))!!}</td>
										<td class="align-middle" data-label="Hora">{!!date('H:i', strtotime($value->created_at))!!}</td>
										<td class="align-middle" data-label="Status">{!!$value->status()!!}</td>
										<td class="align-middle" data-label="Ações">
											<a href="{!!route('cms-integration-queue-show', [$value->id, http_build_query(Request::input())])!!}" class="btn btn-primary">Detalhes</a>
											@if ($value->status == 3)
												<a href="{!!route('cms-integration-set-to-queue', [$value->id, http_build_query(Request::input())])!!}" class="btn btn-success mx-1">Processar novamente</a>
											@endif
										</td>
									</tr>
								@endforeach
							</tbody>
						</table>
					    <div class="row">
					    	<div class="col-12">
					    		 {!!$integration_queue->appends(Request::input())->render()!!}
					    	</div>
					    </div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
@include('cms.includes.footer')
@endsection