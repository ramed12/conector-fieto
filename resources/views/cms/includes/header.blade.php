<header class="header-nav">
	<nav class="navbar navbar-expand-lg navbar-light bg-primary">
		<a class="navbar-brand" href="{!!route('cms-home')!!}">
			<img src="{!!asset('img/logo-gao.png')!!}" class="d-inline-block align-middle mr-2" alt="GAO Connector">
			Connector
		</a>		
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target=".collapse2"> 
            <span class="navbar-toggler-icon"></span> 
        </button>
        <div class="navbar-collapse collapse collapse2">
            <ul class="navbar-nav ml-auto">          
                <li class="nav-item"> 
                    <a class="nav-link" href="{!!route('cms-home')!!}">Inicio <span class="sr-only">(current)</span></a> 
                </li> 
                <li class="nav-item dropdown usuario">         
                    <a class="nav-link dropdown-toggle" href="" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Integrações</a>        
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                        @foreach ($schedules_menu as $value)
                           <a class="dropdown-item" href="{!!route($value->command, base64_encode($value->command))!!}">{!!$value->title!!}</a>
                        @endforeach
                    </div>          
                </li> 
                <li class="nav-item dropdown usuario">         
                    <a class="nav-link dropdown-toggle" href="" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Logs</a>        
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="{!!route('cms-integration-queue')!!}">
                           Logs de Integrações
                        </a>
                        <a class="dropdown-item" href="{!!route('cms-logs-erros')!!}">
                           Logs de Erros
                        </a>
                    </div>          
                </li> 
                <li class="nav-item dropdown usuario">         
                    <a class="nav-link dropdown-toggle" href="" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Configurações</a>        
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="{!!route('cms-schedules')!!}">
                            Schedules Integrações
                        </a>
                        <a class="dropdown-item" href="{!!route('cms-from-to')!!}">
                            Tabela De/Para
                        </a>
                        <a class="dropdown-item" href="{!!route('cms-users')!!}">
                            Usuários
                        </a>
                    </div>          
                </li> 
                <li class="nav-item dropdown usuario">         
                    <a class="nav-link dropdown-toggle" href="" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Manutenção</a>        
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="{!!route('cms-database-update')!!}">
                            GAO Connector Migração
                        </a>
                    </div>          
                </li>  
                                
                <li class="nav-item dropdown usuario">         
                    <a class="nav-link dropdown-toggle" href="" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Autenticação</a>        
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="{!!route('cms-my-data')!!}">
                             Meus Dados
                        </a>
                        <a class="dropdown-item" href="{!!route('auth-logout')!!}">
                            Sair
                        </a>
                    </div>          
                </li>        
            </ul>
        </div>
	</nav>
</header>