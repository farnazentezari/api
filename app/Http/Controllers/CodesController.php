<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Codes;
use App\Http\Controllers\ApiController;
// use OpenAI\Laravel\Facades\OpenAI;
use Orhanerday\OpenAi\OpenAi;



class CodesController extends Controller
{
    public function __construct()
    {
        $this->api = new ApiController();
    }
    public function store(Request $request){

       
        $open_ai = new OpenAi(env('OPENAI_API_KEY'));
        // var_dump($open_ai);die;
        if ($request->language != 'html' || $request->language == 'none') {
            $prompt = "You are a helpful assistant that writes code. Write a good code in " . $request->language . ' programming language';
        } elseif ($request->language == 'html') {
            $prompt = "You are a helpful assistant that writes html code.";
        } else {
            $prompt = "You are a helpful assistant that writes code.";
        }
       
    
        $complete = $open_ai->chat([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    "role" => "system",
                    "content" => $prompt,
                ],
                [
                    "role" => "user",
                    "content" => $request->instructions,
                ],
            ],
            'temperature' => 1,
            'max_tokens' => 3500,
        ]);

        $response = json_decode($complete , true);
        var_dump($response);die;
        // $response=$this->api->callApi($request->all());
        // var_dump($response);die;
        // echo json_encode($response);
    }
}
