<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Codes;
use App\Http\Controllers\ApiController;
use OpenAI\Laravel\Facades\OpenAI;


class CodesController extends Controller
{
    public function __construct()
    {
        $this->api = new ApiController();
    }
    public function store(Request $request){

        // var_dump(config('services.openai.key'));
        // $open_ai = new OpenAI(config('services.openai.key'));

        // var_dump($open_ai);die;
        $client = OpenAI::client(env('OPENAI_API_KEY'));
        var_dump($client);die;
        if ($request->language != 'html' || $request->language == 'none') {
            $prompt = "You are a helpful assistant that writes code. Write a good code in " . $request->language . ' programming language';
        } elseif ($request->language == 'html') {
            $prompt = "You are a helpful assistant that writes html code.";
        } else {
            $prompt = "You are a helpful assistant that writes code.";
        }
       

        $complete = $client->chat([
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
        // $response=$this->api->callApi($request->all());
        // var_dump($response);die;
        // echo json_encode($response);
    }
}
