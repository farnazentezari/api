<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiController extends Controller
{
    //


    public function callApi(){

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://davinci.virmuni.com/user/code/process',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
            "language":"python",
            "instructions":"cdsdsdsds",
            "document":"new code"
        }',
        CURLOPT_HTTPHEADER => array(
            'apiKey: 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',
            'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
    
}
