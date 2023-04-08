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
						<a href="{!!route('cms-users', http_build_query(Request::input()))!!}"><i class="fa fa-angle-double-right ml-1" aria-hidden="true"></i> Usuários</a>
					</li>
				</ul>
			</div>		
		</div>
		@include('layout.alerts')
		<div class="row">
			<div class="col-12">
		        <div class="card mb-4">       
		            <div class="card-body">		                
				        {!! Form::model(Request::input(), ['method' => 'get', 'autocomplete' => 'on', 'route' => ['cms-users', http_build_query(Request::input())], 'class' => 'my-2']) !!}      		    
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
			            			   {!!Form::select('filter_status',['1' => 'Ativo', '2' => 'Inativo'],  null, ['placeholder' => 'Selecione', 'class' => 'form-control']) !!}				            			   
			            			   @if (!empty($errors->first('filter_status')))
		            			 		<label class="error">{!!$errors->first('filter_status')!!}</label>   
		            			 	   @endif 
				                    </div>
								</div>
							</div>
							<div class="row">
								<div class="col-12">
									<button type="submit" class="btn btn-success float-right ml-1">Pesquisar</button>
                					<a href="{!!route('cms-users-create', http_build_query(Request::input()))!!}" class="btn btn-primary  float-right ml-1" title="Adicionar Usuário">Adicionar Usuário</a>
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
					<div class="card-header bg-primary">Usuários</div>
					<div class="card-body">
						<table class="table">
							<thead>
					          <tr>
					            <th>Código</th>
					            <th>Nome</th>   
					            <th>Email</th> 
					            <th>Status</th>   
					            <th>Ação</th>
					          </tr>
					        </thead>
					        <tbody>
					        	@foreach ($users as $value)
					        		<tr>
						                <td  class="align-middle" data-label="Codigo">{!!$value->id!!}</td>
						                <td  class="align-middle" data-label="Nome">{!!$value->first_name!!}</td>
						                <td  class="align-middle" data-label="Email">{!!$value->email!!}</td>     
						                <td  class="align-middle" data-label="Status">{!!$value->status()!!}</td>     
						                <td  class="align-middle" data-label="Ações">
						                	<a href="{!!route('cms-users-show', [$value->id, http_build_query(Request::input())])!!}" class='btn  btn-primary ml-1'>Detalhes</a>
						                	<a href="{!!route('cms-users-delete', [$value->id, http_build_query(Request::input())])!!}" class='btn  btn-danger ml-1'>Excluir</a>
						                </td>  
						            </tr>
					        	@endforeach
					        </tbody>
					    </table>
					    <div class="row">
					    	<div class="col-12">
					    		 {!!$users->appends(Request::input())->render()!!}
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