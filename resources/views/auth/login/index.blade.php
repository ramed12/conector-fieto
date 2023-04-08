@extends('layout.app')

@section('content')	
<div class="container">
    <div class="row justify-content-md-center">
		<div class="col-md-4 col-md-offset-4 my-4">			
			<div class="card">		
				<div class="card-header text-center">
					<a class="navbar-brand" href="{!!route('cms-home')!!}">
						<img src="{!!asset('img/logo-gao-inverted.png')!!}" class="d-inline-block align-middle mr-2" alt="GAO Connector">
						Connector
					</a>
				</div>
			  	<div class="card-body">
			  		<h3 class="text-center">
			  			<img src="{!!asset("img/fieto.png")!!}" alt="Gao Connector" class="img-fluid mx-auto my-1">
			    	</h3>
			    	@include('layout.alerts')
				    {!! Form::open(['method' => 'post', 'autocomplete' => 'on', 'route' => ['auth', http_build_query(Request::input())], 'class' => 'my-2']) !!}      		    
						<div class="row">
							<div class="col-12">
								<div class="form-group">
					    		    {!!Form::email('email', null, ['class' => 'form-control', 'placeholder' => 'Email']) !!}
					    		    @if (!empty($errors->first('email')))
		            			 		<label class="error">{!!$errors->first('email')!!}</label>   
		            			 	@endif 
					    		</div>
					    		<div class="form-group">
					    			{!!Form::password('password', ['class' => 'form-control', 'placeholder' => 'Senha']) !!}
					    			@if (!empty($errors->first('password')))
		            			 		<label class="error">{!!$errors->first('password')!!}</label>   
		            			 	@endif 
								</div>							
								<div class="form-group">
									<a href="{!!route('auth-i-forgot-my-password')!!}">Esqueceu sua senha?</a>
								</div>
								<div class="form-group">
									<button type="submit"   class="btn btn-primary btn-block">ACESSAR SISTEMA</button>
								</div>
							</div>
						</div>		
			      	{!! Form::close() !!}
			  	</div>
			</div>
			<div class="text-center my-2 corporate">
				Desenvolvido por <a href="https://gaotecnologia.com.br" target="_blank"><strong>Gao Tecnologia</strong></a>. <br>
			</div>
		</div>
	</div>
</div>
@endsection