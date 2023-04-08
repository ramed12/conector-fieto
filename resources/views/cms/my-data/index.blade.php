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
						<a><i class="fa fa-angle-double-right ml-1" aria-hidden="true"></i> Autenticação </a>
					</li>
					<li>
						<a href="{!!route('cms-my-data', http_build_query(Request::input()))!!}"><i class="fa fa-angle-double-right ml-1" aria-hidden="true"></i> Meus Dados</a>
					</li>
				</ul>
			</div>		
		</div>
		@include('layout.alerts')
		<div class="row">
			<div class="col-12">
				<div class="card">
					<div class="card-header bg-primary">Meus Dados</div>
					<div class="card-body">
						{!! Form::model($users, ['method' => 'put', 'autocomplete' => 'off', 'route' => ['cms-my-data', http_build_query(Request::input())], 'class' => 'my-2']) !!}      		    
							<div class="row">
								<div class="col-12 col-lg-3 col-md-3 col-sm-3">
									<div class="form-group">                    
				                       {!!Form::label('first_name', 'Nome:')!!}
			            			   {!!Form::text('first_name', null, ['class' => 'form-control']) !!}				            			   
			            			   @if (!empty($errors->first('first_name')))
		            			 		<label class="error">{!!$errors->first('first_name')!!}</label>   
		            			 	   @endif 
				                    </div>
								</div>	
								<div class="col-12 col-lg-3 col-md-3 col-sm-3">
									<div class="form-group">                    
				                       {!!Form::label('last_name', 'Sobrenome:')!!}
			            			   {!!Form::text('last_name', null, ['class' => 'form-control']) !!}				            			   
			            			   @if (!empty($errors->first('last_name')))
		            			 		<label class="error">{!!$errors->first('last_name')!!}</label>   
		            			 	   @endif 
				                    </div>
								</div>	
								<div class="col-12 col-lg-3 col-md-3 col-sm-3">
									<div class="form-group">                    
				                       {!!Form::label('email', 'Email:')!!}
			            			   {!!Form::email('email', null, ['class' => 'form-control']) !!}				            			   
			            			   @if (!empty($errors->first('email')))
		            			 		<label class="error">{!!$errors->first('email')!!}</label>   
		            			 	   @endif 
				                    </div>
								</div>							
								<div class="col-12 col-lg-3 col-md-3 col-sm-3">
									<div class="form-group">                    
				                       {!!Form::label('password', 'Senha:')!!}
			            			   {!!Form::password('password', ['class' => 'form-control', 'minlength' => '6']) !!}				            			   
			            			   @if (!empty($errors->first('password')))
		            			 		<label class="error">{!!$errors->first('password')!!}</label>   
		            			 	   @endif 
				                    </div>
								</div>
							</div>
							<div class="row">
								<div class="col-12">
									<button type="submit" class="btn btn-primary float-right ml-2">Salvar</button>
                					<a href="{!!route('cms-home')!!}" class="btn btn-secondary float-right" title="voltar">Voltar</a>
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