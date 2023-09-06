<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Codes;
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

    }
    public function store(Request $request){

        $request->message_code = strtoupper(Str::random(10));
        $open_ai = new OpenAi(env('OPENAI_API_KEY'));
        // var_dump($open_ai);die;
        $max_results = (int)$request->max_results;
        $plan_type = 'free';  

        $prompt = $request->title;
        
        if ($request->style != 'none') {
            $prompt .= ', ' . $request->style; 
        } 
        
        if ($request->lightning != 'none') {
            $prompt .= ', ' . $request->lightning; 
        } 
        
        if ($request->artist != 'none') {
            $prompt .= ', ' . $request->artist; 
        }
        
        if ($request->medium != 'none') {
            $prompt .= ', ' . $request->medium; 
        }
        
        if ($request->mood != 'none') {
            $prompt .= ', ' . $request->mood; 
        }
       
    
        $complete = $open_ai->image([
            'prompt' => $prompt,
            'size' => $request->resolution,
            'n' => $max_results,
            "response_format" => "url",
        ]);

        // var_dump($complete);die;
        $response = json_decode($complete , true);
        if (isset($response['data'])) {
            if (count($response['data']) > 1) {
                foreach ($response['data'] as $key => $value) {
                    $url = $value['url'];

                    $curl = curl_init();
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($curl, CURLOPT_URL, $url);
                    $contents = curl_exec($curl);
                    curl_close($curl);

                    $name = Str::random(10) . '.png';

                    
                    // Storage::disk('local')->put('images/' . $name, $contents);
                    // $image_url = 'images/' . $name;
                    // $storage = 'local';
                    

                    // $content = new Image();
                    // $content->user_id = auth()->user()->id;
                    // $content->name = $request->name . '-' . $key;
                    // $content->description = $request->title;
                    // $content->resolution = $request->resolution;
                    // $content->image = $image_url;
                    // $content->plan_type = $plan_type;
                    // $content->storage = $storage;
                    // $content->expires_at = $expiration;
                    // $content->image_name = 'images/' . $name;
                    // $content->save();
                }
            } else {
                $url = $response['data'][0]['url'];

                $curl = curl_init();
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_URL, $url);
                $contents = curl_exec($curl);
                curl_close($curl);



                // $content = new Image();
                // $content->user_id = auth()->user()->id;
                // $content->name = $request->name;
                // $content->description = $request->title;
                // $content->resolution = $request->resolution;
                // $content->image = $image_url;
                // $content->plan_type = $plan_type;
                // $content->storage = $storage;
                // $content->expires_at = $expiration;
                // $content->image_name = 'images/' . $name;
                // $content->save();
            }
             $data['url']= $url;
            $data['status'] = 'success';
           
        } else {

            $message = $response['error']['message'];

            $data['status'] = 'error';
            $data['message'] = $message;
        }
        echo json_encode($data);
    }
}
