<?php
namespace App\Traits;
use Illuminate\Support\Facades\Http;
use Exception;
use Mail;

trait AlertMail {

    private function sendAlert($to_mail, $error, $routine_title) {

        try {

            if (!empty($to_mail)) {
                return Mail::send('email/alert-error', ['error' => $error, 'to_mail' => $to_mail, 'routine_title' => $routine_title], function ($email) use ($error, $to_mail, $routine_title) {
                    $email->from(env("MAIL_FROM_ADDRESS"), env("MAIL_FROM_NAME"));
                    $email->to(explode(',', $to_mail), "GAO Connector")->subject('Falha na Integração -'.$routine_title);
                }); 
            }                     
 
        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
