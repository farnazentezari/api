<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chats;
use App\Models\ChatHistory;
use App\Http\Controllers\ApiController;
// use OpenAI\Laravel\Facades\OpenAI;
use Orhanerday\OpenAi\OpenAi;
use Illuminate\Support\Str;


class ChatsController extends Controller
{
    public function __construct()
    {
        $this->api = new ApiController();
    }
    public function index(){

        $chats=Chats::all();

        $data['status']="sucess";
        $data['data']=$chats;
        echo json_encode($data);

    }
    public function store(Request $request){


        $main_chat = Chats::where('chat_code', $request->chat_code)->first();

        if ($request->message_code == '') {
            $messages = ["role"=>"system","content"=>$main_chat->prompt];            
            $messages[] = ["role"=>"user","content"=>$request->input('message')];

            
            $chat = new ChatHistory();
            $chat->title = 'New Chat';
            $chat->chat_code = $request->chat_code;
            $chat->message_code = strtoupper(Str::random(10));
            $chat->messages = 1;
            $chat->chat = $messages;
            // var_dump(json_encode($messages));die;
            $chat->save();
        } else {
            $chat_message = ChatHistory::where('message_code', $request->message_code)->first();

            if ($chat_message) {

                if (is_null($chat_message->chat)) {
                    $messages[] = ['role' => 'system', 'content' => $main_chat->prompt]; 
                } else {
                    $messages = $chat_message->chat;
                }
                
                array_push($messages, ['role' => 'user', 'content' => $request->input('message')]);
                $chat_message->messages = ++$chat_message->messages;
                $chat_message->chat = $messages;
                $chat_message->save();
            } else {
                $messages[] = ['role' => 'system', 'content' => $main_chat->prompt];            
                $messages[] = ['role' => 'user', 'content' => $request->input('message')];

                $chat = new ChatHistory();
                $chat->title = 'New Chat';
                $chat->chat_code = $request->chat_code;
                $chat->message_code = $request->message_code;
                $chat->messages = 1;
                $chat->chat = $messages;
                $chat->save();
            }
        }

        session()->put('message_code', $request->message_code);

        // return response()->json(['status' => 'success', 'old'=> $balance, 'current' => ($balance - $words)]);
        

        $message_code = $chat->message_code;

        return response()->stream(function () use($message_code) {

            $open_ai = new OpenAi(env('OPENAI_API_KEY'));

            $chat_message = ChatHistory::where('message_code', $message_code)->first();
            $messages = $chat_message->chat;

            $text = "";

            # Apply proper model based on role and subsciption
            
            $opts = [
                'model' => 'gpt-3.5-turbo',
                'messages' => $messages,
                'temperature' => 1.0,
                'frequency_penalty' => 0,
                'presence_penalty' => 0,
                'stream' => true
            ];
            
            
            $complete = $open_ai->chat($opts, function ($curl_info, $data) use (&$text) {
                if ($obj = json_decode($data) and $obj->error->message != "") {
                    error_log(json_encode($obj->error->message));
                } else {
                    echo $data;

                    $clean = str_replace("data: ", "", $data);
                    $first = str_replace("}\n\n{", ",", $clean);
    
                    if(str_contains($first, 'assistant')) {
                        $raw = str_replace('"choices":[{"delta":{"role":"assistant"', '"random":[{"alpha":{"role":"assistant"', $first);
                        $response = json_decode($raw, true);
                    } else {
                        $response = json_decode($clean, true);
                    }    
        
                    if ($data != "data: [DONE]\n\n" and isset($response["choices"][0]["delta"]["content"])) {
                        $text .= $response["choices"][0]["delta"]["content"];
                    }
                }

                echo PHP_EOL;
                // ob_flush();
                // flush();
                var_dump(strlen($data));die;
                return strlen($data);
            });

            # Update credit balance
            // $words = count(explode(' ', ($text)));
            // $this->updateBalance($words);  
            
            array_push($messages, ['role' => 'assistant', 'content' => $text]);
            $chat_message->messages = ++$chat_message->messages;
            $chat_message->chat = $messages;
            $chat_message->save();
           
        }, 200, [
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'text/event-stream',
        ]);
    }
}
