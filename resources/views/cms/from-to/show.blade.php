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
						<a><i class="fa fa-angle-double-right ml-1" aria-hidden="true"></i> Configurações </a>
					</li>
					<li>
						<a href="{!!route('cms-from-to', http_build_query(Request::input()))!!}"><i class="fa fa-angle-double-right ml-1" aria-hidden="true"></i> Tabela De/Para</a>
					</li>
					<li>
						<a><i class="fa fa-angle-double-right ml-1" aria-hidden="true"></i> {!!(!isset($from_to)) ? 'Adicionar' : 'Editar'!!}</a>
					</li>
				</ul>
			</div>		
		</div>
		@include('layout.alerts')
		<div class="row">
			<div class="col-12">
				<div class="card">
					<div class="card-header bg-primary">Tabela De/Para</div>
					<div class="card-body">
						@if(isset($from_to))
			                {!! Form::model($from_to, ['route' => ['cms-from-to-update', $from_to->id, http_build_query(Request::input())], 'method' => 'put']) !!}
			            @else
			                {!! Form::open(['method' => 'post', 'autocomplete' => 'off', 'route' => ['cms-from-to-create', http_build_query(Request::input())]]) !!}
			            @endif 
			            	<div class="row">
			            		<div class="col-12 col-lg-12 col-md-12 col-sm-12">
									<div class="form-group">                    
				                       {!!Form::label('command', 'Rotina:')!!}
			            			   {!!Form::select('command', $schedule,  null, ['placeholder' => 'Selecione', 'class' => 'form-control']) !!}				            			   
			            			   @if (!empty($errors->first('command')))
		            			 		<label class="error">{!!$errors->first('command')!!}</label>   
		            			 	   @endif 
				                    </div>
								</div>
			            	</div>
			            	<div class="row">
			            		<div class="col-12 col-lg-3 col-md-3 col-sm-3">
									<div class="form-group">                    
				                       {!!Form::label('filial', 'Código da Filial:')!!}
			            			   {!!Form::text('filial', null, ['class' => 'form-control']) !!}				            			   
			            			   @if (!empty($errors->first('filial')))
		            			 		<label class="error">{!!$errors->first('filial')!!}</label>   
		            			 	   @endif 
				                    </div>
								</div>
								<div class="col-12 col-lg-3 col-md-3 col-sm-3">
									<div class="form-group">                    
				                       {!!Form::label('field', 'Nome do Campo:')!!}
			            			   {!!Form::text('field', null, ['class' => 'form-control']) !!}				            			   
			            			   @if (!empty($errors->first('field')))
		            			 		<label class="error">{!!$errors->first('field')!!}</label>   
		            			 	   @endif 
				                    </div>
								</div>	
								<div class="col-12 col-lg-3 col-md-3 col-sm-3">
									<div class="form-group">                    
				                       {!!Form::label('value_origin', 'Valor de Origem:')!!}
			            			   {!!Form::text('value_origin', null, ['class' => 'form-control']) !!}				            			   
			            			   @if (!empty($errors->first('value_origin')))
		            			 		<label class="error">{!!$errors->first('value_origin')!!}</label>   
		            			 	   @endif 
				                    </div>
								</div>	
								<div class="col-12 col-lg-3 col-md-3 col-sm-3">
									<div class="form-group">                    
				                       {!!Form::label('value_destiny', 'Valor de Destino:')!!}
			            			   {!!Form::text('value_destiny', null, ['class' => 'form-control']) !!}				            			   
			            			   @if (!empty($errors->first('value_destiny')))
		            			 		<label class="error">{!!$errors->first('value_destiny')!!}</label>   
		            			 	   @endif 
				                    </div>
								</div>
								<div class="col-12 col-lg-12 col-md-12 col-sm-12">
									<div class="form-group">                    
				                       {!!Form::label('text', 'Descrição:')!!}
			            			   {!!Form::textarea('text', null, ['class' => 'form-control', 'rows' => '5']) !!}				            			   
			            			   @if (!empty($errors->first('text')))
		            			 		<label class="error">{!!$errors->first('text')!!}</label>   
		            			 	   @endif 
				                    </div>
								</div>
							</div>
							<div class="row">
								<div class="col-12">
									<button type="submit" class="btn btn-primary float-right ml-2">Salvar</button>
                					<a href="{!!route('cms-from-to')!!}" class="btn btn-secondary float-right" title="voltar">Voltar</a>
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