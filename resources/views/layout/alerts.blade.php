@if (Session::has('alert'))
    <div class="row">
    	<div class="col-12">
    		<div role="alert" class="alert alert-{!!session('alert.code')!!} alert-dismissible fade show">
    			<i class="fas fa-exclamation-triangle mr-2"></i> {!! session('alert.text') !!}
    			<button type="button" class="close" data-dismiss="alert" aria-label="Close">
				    <span aria-hidden="true">&times;</span>
				</button>
    		</div>
    	</div>
    </div>
@endif	