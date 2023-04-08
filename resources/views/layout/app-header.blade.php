<!DOCTYPE html>
<html lang="pt-BR" itemscope itemtype="https://schema.org">
<head>
	<meta charset="UTF-8">
	<title>Gao Connector - Fieto</title>
	<meta name="csrf-token" content="{!!csrf_token()!!}"/>
	<meta name="app-url" 	content="{!!url("/")!!}"/>
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="viewport"       content="width=device-width,initial-scale=1">
    <meta name="robots"         content="index,follow,noodp"/>
    <meta name="language"       content="portuguese"/>  
    <meta name="referrer" content="origin"> 
    @if (file_exists("css/gao-connector.css"))
      <link rel="stylesheet" href="{{asset(elixir('css/gao-connector.css'))}}">  
    @endif
    <link rel="shortcut icon" href="{!!asset("img/favicon.png")!!}" type="image/png">
</head>
<body class="{!!(Route::currentRouteName() == 'auth' || Route::currentRouteName() == 'auth-i-forgot-my-password' || Route::currentRouteName() == 'auth-reset-password') ? 'auth' : ''!!}">
	
