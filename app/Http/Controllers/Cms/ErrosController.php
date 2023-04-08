<?php

namespace App\Http\Controllers\Cms;

use Illuminate\Http\Request;
use App\Http\Controllers\CmsController;

class ErrosController extends CmsController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $types = array( 'log');
        $data  = [];

        if ( $handle = opendir(public_path('../storage/logs/')) ) {
            while ( $entry = readdir( $handle ) ) {
                $ext = strtolower( pathinfo( $entry, PATHINFO_EXTENSION) );
                if( in_array( $ext, $types ) ) {

                    $data[] =  $entry;;
                }
            }
            closedir($handle);
        } 

        return view("cms/log-de-erros/index", array(
            "data" => $data
        ));  
    }

    public function show(Request $request, $file)
    {
        $filename = public_path('../storage/logs/').base64_decode($file);

        header("Pragma: public");
        header("Expires: 0"); // set expiration time
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment; filename=".basename($filename).";");
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: ".filesize($filename));
        
        @readfile($filename);
        exit(0);
       
    }

    public function delete(Request $request, $file)
    {
        $filename = public_path('../storage/logs/').base64_decode($file);

        if(file_exists($filename)) {
            @unlink($filename);
            $request->session()->flash('alert', array('code'=> 'success', 'text'  => "Dado excluido com sucesso!"));
        }

        return redirect(route('cms-logs-erros'));       
    }
}
