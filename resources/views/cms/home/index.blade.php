@extends('layout.app')
@section('content')	
@include('cms.includes.header')
<section class="wrapper">
	<div class="container-fluid">
		<div class="row">	
			<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
				<ul class="breadcrumb">
					<li>
						<a href="{!!route('cms-home')!!}"><i class="fa fa-home" aria-hidden="true"></i> Inicio</a>
					</li>
				</ul>
			</div>		
		</div>
		@include('layout.alerts')
		<div class="row">
			<div class="col-12">
				<div class="card">
					<div class="card-header bg-primary">Últimos Registros Integrados</div>
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
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
@include('cms.includes.footer')
@endsection