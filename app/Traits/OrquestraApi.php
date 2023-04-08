<?php
namespace App\Traits;
use Illuminate\Support\Facades\Http;
use Exception;
use Auth;
use Session;
use App\Model\Control_tokens;

trait OrquestraApi {

    private function createToken() {

        try {

            $responseToken = Http::withOptions([
                "verify" => false
            ])
            ->acceptJson()
            ->post(env('WS_ORQUESTRA_AUTH_ENDPOINT'), [
                "email"           => env('WS_ORQUESTRA_EMAIL'),
                "userName"        => env('WS_ORQUESTRA_USERNAME'),
                "password"        => env('WS_ORQUESTRA_PASSWORD'),                
                "persistentLogin" => env('WS_ORQUESTRA_PERSISTENTLOGIN'),
            ]);

            if($responseToken->ok()) {  
                $response = $responseToken->json();

                Control_tokens::updateOrCreate([
                    'system' => 'ORQUESTRA'    
                ], [
                    'payload'  => json_encode($response['data'])
                ]);

            } else {
                throw new \Exception('Falha na obtenção do token no ws orquestra');
            }
            
        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    private function isTokenValid() {

        $token = $this->getToken();

        if(is_null($token)) {
            return false;
        }

        return (date('Y-m-d H:i:s', strtotime($token->expiration)) > strtotime("-1 hours", strtotime(date('Y-m-d H:i:s'))));
    }

    private function getToken() {

        $control_tokens = Control_tokens::where('system', 'ORQUESTRA')->first();

        return (empty($control_tokens)) ? $control_tokens : json_decode($control_tokens->payload);
    }

    private function getAccessToken() {

        if(!$this->isTokenValid()) {
            $this->createToken();
        }

        $token = $this->getToken();

        return  $token->access_token;
    }
}
