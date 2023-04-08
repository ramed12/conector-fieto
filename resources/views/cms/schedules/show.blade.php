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
						<a href="{!!route('cms-schedules', http_build_query(Request::input()))!!}"><i class="fa fa-angle-double-right ml-1" aria-hidden="true"></i> Schedules de Integrações</a>
					</li>
					<li>
						<a><i class="fa fa-angle-double-right ml-1" aria-hidden="true"></i> Editar Schedule</a>
					</li>
				</ul>
			</div>		
		</div>
		@include('layout.alerts')
		<div class="row">
			<div class="col-12">
				<div class="card">
					<div class="card-header bg-primary">Schedules de Integrações</div>
					<div class="card-body">
						@if(isset($schedules))
			                {!! Form::model($schedules, ['route' => ['cms-schedules-update', $schedules->id, http_build_query(Request::input())], 'method' => 'put']) !!}
			            @else
			                {!! Form::open(['method' => 'post', 'autocomplete' => 'off', 'route' => ['cms-schedules-create', http_build_query(Request::input())]]) !!}
			            @endif 
			            <div class="row">
								<div class="col-12 col-lg-12 col-md-12 col-sm-12">
									<div class="form-group">                    
				                       {!!Form::label('title', 'Rotina:')!!}
			            			   {!!Form::text('title', null, ['class' => 'form-control', 'disabled']) !!}				            			   
			            			   @if (!empty($errors->first('title')))
		            			 		<label class="error">{!!$errors->first('title')!!}</label>   
		            			 	   @endif 
				                    </div>
								</div>	
								<div class="col-12 col-lg-12 col-md-12 col-sm-12">
									<div class="form-group">                    
				                       {!!Form::label('text', 'Descrição')!!}
			            			   {!!Form::text('text', null, ['class' => 'form-control', 'disabled']) !!}				            			   
			            			   @if (!empty($errors->first('text')))
		            			 		<label class="error">{!!$errors->first('text')!!}</label>   
		            			 	   @endif 
				                    </div>
								</div>	
								<div class="col-12 col-lg-12 col-md-12 col-sm-12">
									<div class="form-group">                    
				                       {!!Form::label('command', 'Comando:')!!}
			            			   {!!Form::text('command', null, ['class' => 'form-control', 'disabled']) !!}				            			   
			            			   @if (!empty($errors->first('command')))
		            			 		<label class="error">{!!$errors->first('command')!!}</label>   
		            			 	   @endif 
				                    </div>
								</div>
								<div class="col-12 col-lg-12 col-md-12 col-sm-12">
									<div class="form-group">                    
				                       {!!Form::label('pagination', 'Total de Itens a paginar:')!!}
			            			   {!!Form::number('pagination', null, ['class' => 'form-control']) !!}				            			   
			            			   @if (!empty($errors->first('pagination')))
		            			 		<label class="error">{!!$errors->first('pagination')!!}</label>   
		            			 	   @endif 
				                    </div>
								</div>
							</div>
							<div class="row">
								<div class="col-12 col-lg-12 col-md-12 col-sm-12">
									<div class="form-group">                    
				                       {!!Form::label('email', 'Emails para alertas de erros: (separe os emails com virgula)')!!}
			            			   {!!Form::text('email', null, ['class' => 'form-control']) !!}				            			   
			            			   @if (!empty($errors->first('email')))
		            			 		<label class="error">{!!$errors->first('email')!!}</label>   
		            			 	   @endif 
				                    </div>
								</div>
							</div>
							<div class="row">
								<div class="col-12">
									<div class="form-group">                    
				                       {!!Form::label('status', 'Status:')!!}
			            			   {!!Form::select('status',['1' => 'Ativo', '2' => 'Inativo'],  null, ['placeholder' => 'Selecione', 'class' => 'form-control']) !!}				            			   
			            			   @if (!empty($errors->first('status')))
		            			 		<label class="error">{!!$errors->first('status')!!}</label>   
		            			 	   @endif 
				                    </div>
								</div>
							</div>
							<div class="row">
								<div class="col-12">
									<button type="submit" class="btn btn-primary float-right ml-2">Salvar</button>
                					<a href="{!!route('cms-schedules')!!}" class="btn btn-secondary float-right" title="voltar">Voltar</a>
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