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
						<a href="{!!route('cms-my-data', http_build_query(Request::input()))!!}"><i class="fa fa-angle-double-right ml-1" aria-hidden="true"></i> Logs de Erros</a>
					</li>
				</ul>
			</div>		
		</div>
		@include('layout.alerts')
		<div class="row">
			<div class="col-12">
				<div class="card">
					<div class="card-header bg-primary">Logs de Erros</div>
					<div class="card-body">
						<table class="table">
					        <thead>
					          <tr>
					            <th>Codigo</th>
					            <th>Nome</th>    
					            <th>Ação</th>
					          </tr>
					        </thead>
					        <tbody>
					        	@foreach ($data as $key => $value)
					        		<tr>
						                <td class="align-middle" data-label="Codigo">{!!($key +1)!!}</td>
						                <td class="align-middle" data-label="Nome">{!!$value!!}</td>
						                <td class="align-middle" data-label="Ação">
						                	<a href="{!!route('cms-logs-erros-downloads', base64_encode($value))!!}" class='btn  btn-primary'>Download</a>
											<a href="{!!route('cms-logs-erros-downloads-delete', base64_encode($value))!!}" class='btn  btn-danger ml-1'>Excluir</a>
						                </td>  
						            </tr>
					        	@endforeach
					        </tbody>
					    </table>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
@include('cms.includes.footer')
@endsection