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
				</ul>
			</div>		
		</div>
		@include('layout.alerts')
		<div class="row">
			<div class="col-12">
		        <div class="card mb-4">       
		            <div class="card-body">		                
				        {!! Form::model(Request::input(), ['method' => 'get', 'autocomplete' => 'on', 'route' => ['cms-from-to', http_build_query(Request::input())], 'class' => 'my-2']) !!}      		    
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
                					<a href="{!!route('cms-from-to-create', http_build_query(Request::input()))!!}" class="btn btn-primary  float-right ml-1" title="Adicionar Usuário">Adicionar</a>
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
					<div class="card-header bg-primary">Tabela De/Para</div>
					<div class="card-body">
						<table class="table">
							<thead>
					          <tr>
					            <th>Código</th>
					            <th>Filial</th>
					            <th>Rotina</th>
					            <th>Campo</th>  
					            <th>Valor Origem</th> 	  
					            <th>Valor Destino</th> 
					            <th>Descrição</th>   
					            <th>Ação</th>
					          </tr>
					        </thead>
					        <tbody>
					        	@foreach ($from_to as $value)
					        		<tr>
						                <td  class="align-middle" data-label="Codigo">{!!$value->id!!}</td>
						                <td  class="align-middle" data-label="Filial">{!!$value->filial!!}</td>
						                <td  class="align-middle" data-label="Rotina">{!!$value->schedules->title!!}</td>
						                <td  class="align-middle" data-label="Campo">{!!$value->field!!}</td>  
						                <td  class="align-middle" data-label="Valor Origem">{!!$value->value_origin!!}</td> 
						                <td  class="align-middle" data-label="Valor Destino">{!!$value->value_destiny!!}</td>       
						                <td  class="align-middle" data-label="Descrição">{!!Str::limit($value->text, 30)!!}</td>    
						                <td  class="align-middle" data-label="Ações">
						                	<a href="{!!route('cms-from-to-show', [$value->id, http_build_query(Request::input())])!!}" class='btn  btn-primary ml-1'>Detalhes</a>
						                	<a href="{!!route('cms-from-to-delete', [$value->id, http_build_query(Request::input())])!!}" class='btn  btn-danger ml-1'>Excluir</a>
						                </td>  
						            </tr>
					        	@endforeach
					        </tbody>
					    </table>
					    <div class="row">
					    	<div class="col-12">
					    		 {!!$from_to->appends(Request::input())->render()!!}
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