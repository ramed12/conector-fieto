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
			  			<img src="{!!asset("img/fieto.png")!!}" alt="Gao Connector" class="img-fluid mx-auto my-0">
			    	</h3>
				    {!! Form::open(['method' => 'post', 'autocomplete' => 'on', 'route' => ['auth-reset-password', http_build_query(Request::input())], 'class' => 'my-2']) !!}      		    
						<div class="row">
							<div class="col-12 mb-2">
								<h5 class="text-center my-2">Alterar Senha</h5>
							</div>
						</div>
						@include('layout.alerts')
						<div class="row">
							<div class="col-12">
					    		<div class="form-group">
					    			{!!Form::password('password', ['class' => 'form-control', 'placeholder' => 'Senha', 'minlength' => '6']) !!}
					    			@if (!empty($errors->first('password')))
		            			 		<label class="error">{!!$errors->first('password')!!}</label>   
		            			 	@endif 
								</div>
								<div class="form-group">
					    			{!!Form::password('password_confirmation', ['class' => 'form-control', 'placeholder' => 'Confirme a Senha', 'minlength' => '6']) !!}
					    			@if (!empty($errors->first('password_confirmation')))
		            			 		<label class="error">{!!$errors->first('password_confirmation')!!}</label>   
		            			 	@endif 
								</div>
								<div class="form-group">
					    			{!!Form::hidden('email', $email, ['class' => 'form-control', 'placeholder' => 'email']) !!}
					    			{!!Form::hidden('id', $id, ['class' => 'form-control', 'placeholder' => 'id']) !!}
								</div>
								<div class="form-group">
									<button type="submit"   class="btn btn-primary btn-block">ALTERAR</button>
								</div>
							</div>
						</div>				    		
	                    
			      	{!! Form::close() !!}
			  	</div>
			</div>
		</div>
	</div>
</div>
@endsection