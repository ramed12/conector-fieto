<?php
namespace App\Traits;
use Illuminate\Support\Facades\Http;
use Exception;
use Auth;
use Session;
use App\Model\Control_tokens;

trait DynamicsApi {

    private function createToken() {

        try {

            $responseToken = Http::asForm()
            ->withOptions([
                "verify" => false
            ])
            ->post(env('WS_DYNAMICS_AUTH_ENDPOINT'), [
                "grant_type"    => env('WS_DYNAMICS_API_GRANT_TYPE'),
                "client_id"     => env('WS_DYNAMICS_CLIENT_ID'),
                "client_secret" => env('WS_DYNAMICS_CLIENT_SECRET'),                
                "scope"         => env('WS_DYNAMICS_API_SCOPE'),
                "resource"      => env('WS_DYNAMICS_API_RESOURCE')
            ]);

            if($responseToken->ok()) {  

                Control_tokens::updateOrCreate([
                    'system' => 'DYNAMICS'    
                ], [
                    'payload'  => json_encode($responseToken->json())
                ]);

            } else {
                throw new \Exception('Falha na obtenção do token no ws dynamics');
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

        return (date('Y-m-d H:i:s', $token->expires_on) > date('Y-m-d H:i:s'));
    }

    private function getToken() {

        $control_tokens =  Control_tokens::where('system', 'DYNAMICS')->first();

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
