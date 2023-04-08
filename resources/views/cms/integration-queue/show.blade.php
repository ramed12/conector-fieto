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
					<li>
						<a><i class="fa fa-angle-double-right ml-1" aria-hidden="true"></i> Visualizar Logs de Integrações</a>
					</li>
				</ul>
			</div>		
		</div>
		@include('layout.alerts')
		<div class="row">
			<div class="col-12">
				<div class="card">
					<div class="card-header bg-primary">Logs de Integrações</div>
					<div class="card-body">
						<div class="row"></div>
						@if(isset($integration_queue))
			                {!! Form::model($integration_queue, ['route' => ['cms-integration-queue-show', $integration_queue->id, http_build_query(Request::input())], 'method' => 'put']) !!}
			            @else
			                {!! Form::open(['method' => 'post', 'autocomplete' => 'off', 'route' => ['cms-integration-queue-create', http_build_query(Request::input())]]) !!}
			            @endif 
			            <div class="row">
								<div class="col-12 col-lg-2 col-md-2 col-sm-2">
									<div class="form-group">                    
				                       {!!Form::label('id', 'Código:')!!}
			            			   {!!Form::text('id', null, ['class' => 'form-control', 'disabled']) !!}				            			   
			            			   @if (!empty($errors->first('id')))
		            			 		<label class="error">{!!$errors->first('id')!!}</label>   
		            			 	   @endif 
				                    </div>
								</div>
								<div class="col-12 col-lg-5 col-md-5 col-sm-5">
									<div class="form-group">                    
				                       {!!Form::label('origin', 'Origem:')!!}
			            			   {!!Form::text('origin', null, ['class' => 'form-control', 'disabled']) !!}				            			   
			            			   @if (!empty($errors->first('origin')))
		            			 		<label class="error">{!!$errors->first('origin')!!}</label>   
		            			 	   @endif 
				                    </div>
								</div>
								<div class="col-12 col-lg-5 col-md-5 col-sm-5">
									<div class="form-group">                    
				                       {!!Form::label('destiny', 'Destino:')!!}
			            			   {!!Form::text('destiny', null, ['class' => 'form-control', 'disabled']) !!}				            			   
			            			   @if (!empty($errors->first('destiny')))
		            			 		<label class="error">{!!$errors->first('destiny')!!}</label>   
		            			 	   @endif 
				                    </div>
								</div>
								<div class="col-12 col-lg-2 col-md-2 col-sm-2">
									<div class="form-group">                    
				                       {!!Form::label('created_at', 'Data de Criação:')!!}
			            			   {!!Form::date('created_at', date('Y-m-d', strtotime($integration_queue->created_at)), ['class' => 'form-control', 'disabled']) !!}				            			   
			            			   @if (!empty($errors->first('created_at')))
		            			 		<label class="error">{!!$errors->first('created_at')!!}</label>   
		            			 	   @endif 
				                    </div>
								</div>
								<div class="col-12 col-lg-2 col-md-2 col-sm-2">
									<div class="form-group">                    
				                       {!!Form::label('time', 'Hora de Criação:')!!}
			            			   {!!Form::text('time', date('H:i', strtotime($integration_queue->created_at)), ['class' => 'form-control', 'disabled']) !!}				            			   
			            			   @if (!empty($errors->first('time')))
		            			 		<label class="error">{!!$errors->first('time')!!}</label>   
		            			 	   @endif 
				                    </div>
								</div>
								<div class="col-12 col-lg-8 col-md-8 col-sm-8">
									<div class="form-group">                    
				                       {!!Form::label('status', 'Status:')!!}
			            			   {!!Form::select('status',['1' => 'Integrado', '2' => 'Não Integrado', '3' => 'Falha No Processamento'],  null, ['placeholder' => 'Selecione', 'class' => 'form-control', 'disabled']) !!}				            			   
			            			   @if (!empty($errors->first('status')))
		            			 		<label class="error">{!!$errors->first('status')!!}</label>   
		            			 	   @endif 
				                    </div>
								</div>
							</div> 
							<div class="row">
								<div class="col-12">
									<div class="form-group">                    
				                       {!!Form::label('error_log', 'Log:')!!}
				                       <textarea class="form-control" disabled rows="6">
				                       	@php
				                       		print(base64_decode($integration_queue->error_log))
				                       	@endphp
				                       </textarea>
			            			   @if (!empty($errors->first('error_log')))
		            			 		<label class="error">{!!$errors->first('error_log')!!}</label>   
		            			 	   @endif 
				                    </div>
								</div>
							</div>
							<div class="row">
								<div class="col-12">
									<div id="table" class="table-editable">
								      <table class="table table-bordered table-responsive table-striped text-center">
								        <thead>
								          <tr>
								          	@php
								          		$properties = json_decode(base64_decode($integration_queue->properties));
								          	@endphp
								          	@foreach ($properties as $key => $value)
								          		@if (is_object($value) || is_array($value))							          			
								          			@foreach ($properties->{$key} as $itemKey => $itens)								          				
								          				@if (is_array($itens) || is_object($itens))
								          					@if (is_object($itens))
								          						@foreach ($itens as $i => $item)
									          						<th>{!!$i!!}</th> 
									          					@endforeach
								          					@else
								          						@foreach ($itens[$itemKey] as $i => $item)
									          						<th>{!!$i!!}</th> 
									          					@endforeach
								          					@endif								          					
								          				@else
								          					<th>{!!$itemKey!!}</th> 						          					
								          				@endif								          				
								          			@endforeach
								          		@else
								          			<th>{!!$key!!}</th> 
								          		@endif								          		
								          	@endforeach									              
								          </tr>
								        </thead>
								        <tbody>
								        	<tr>
									          	@foreach ($properties as $key => $value)
									          		@if (is_object($value) || is_array($value))							          			
									          			@foreach ($properties->{$key} as $itemKey => $itens)								          				
									          				@if (is_array($itens) || is_object($itens))
									          					@if (is_object($itens))
									          						@foreach ($itens as $i => $item)
										          						<td>{!!$item!!}</td> 
										          					@endforeach
									          					@else
									          						@foreach ($itens[$itemKey] as $i => $item)
										          						<td>{!!$item!!}</td> 
										          					@endforeach
									          					@endif								          					
									          				@else
									          					<td>{!!$itens!!}</td> 						          					
									          				@endif								          				
									          			@endforeach
									          		@else
									          			<td>{!!$value!!}</td> 
									          		@endif								          		
									          	@endforeach									              
									        </tr>										        
								        </tbody>
								      </table>
								    </div>
								</div>
							</div>							
							<div class="row">
								<div class="col-12">
                					<a href="{!!route('cms-integration-queue')!!}" class="btn btn-secondary float-right" title="voltar">Voltar</a>
								</div>
							</div>			
				      	{!! Form::close() !!}
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
@include('cms.includes.footer')
@endsection