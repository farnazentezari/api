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
       
    
        $req=[
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
        ];
        $complete = $open_ai->chat($req);

        $response = json_decode($complete , true);
        // var_dump($response);die;
        
        if (isset($response['choices'])) {

            $text = $response['choices'][0]['message']['content'];
            $tokens = $response['usage']['total_tokens'];

            // $code = new Codes();
            // $code->lang = $request->language;
            // $code->text = $request->instructions;
            // $code->request = json_encode($req);
            // $code->response = $response;
            // $code->save();

            $data['text'] = $text;
            $data['status'] = 'success';
            // $data['id'] = $code->id;

        } else {

            if (isset($response['error']['message'])) {
                $message = $response['error']['message'];
            } else {
                $message = __('There is an issue with your openai account');
            }

            $data['status'] = 'error';
            $data['message'] = $message;
            
        }
         echo json_encode($data);
        // $response=$this->api->callApi($request->all());
        // var_dump($response);die;
        // echo json_encode($response);
    }
}
