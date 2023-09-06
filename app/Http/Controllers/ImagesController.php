<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Codes;
use App\Http\Controllers\ApiController;
// use OpenAI\Laravel\Facades\OpenAI;
use Orhanerday\OpenAi\OpenAi;



class ImagesController extends Controller
{
    public function __construct()
    {
        $this->api = new ApiController();
    }
    public function store(Request $request){

       
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

                    
                    Storage::disk('local')->put('images/' . $name, $contents);
                    $image_url = 'images/' . $name;
                    $storage = 'local';
                    

                    if ($plan) {
                        if (is_null($plan->image_storage_days)) {
                            if (config('settings.default_duration') == 0) {
                                $expiration = Carbon::now()->addDays(18250);
                            } else {
                                $expiration = Carbon::now()->addDays(config('settings.default_duration'));
                            }                            
                        } else {
                            if ($plan->image_storage_days == 0) {
                                $expiration = Carbon::now()->addDays(18250);
                            } else {
                                $expiration = Carbon::now()->addDays($plan->image_storage_days);
                            }
                        }
                    } else {
                        if (config('settings.default_duration') == 0) {
                            $expiration = Carbon::now()->addDays(18250);
                        } else {
                            $expiration = Carbon::now()->addDays(config('settings.default_duration'));
                        } 
                    }

                    $content = new Image();
                    $content->user_id = auth()->user()->id;
                    $content->name = $request->name . '-' . $key;
                    $content->description = $request->title;
                    $content->resolution = $request->resolution;
                    $content->image = $image_url;
                    $content->plan_type = $plan_type;
                    $content->storage = $storage;
                    $content->expires_at = $expiration;
                    $content->image_name = 'images/' . $name;
                    $content->save();
                }
            } else {
                $url = $response['data'][0]['url'];

                $curl = curl_init();
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_URL, $url);
                $contents = curl_exec($curl);
                curl_close($curl);


                $name = Str::random(10) . '.png';

                if (config('settings.default_storage') == 'local') {
                    Storage::disk('local')->put('images/' . $name, $contents);
                    $image_url = 'images/' . $name;
                    $storage = 'local';
                } elseif (config('settings.default_storage') == 'aws') {
                    Storage::disk('s3')->put('images/' . $name, $contents, 'public');
                    $image_url = Storage::disk('s3')->url('images/' . $name);
                    $storage = 'aws';
                } elseif (config('settings.default_storage') == 'wasabi') {
                    Storage::disk('wasabi')->put('images/' . $name, $contents);
                    $image_url = Storage::disk('wasabi')->url('images/' . $name);
                    $storage = 'wasabi';
                }

                if ($plan) {
                    if (is_null($plan->image_storage_days)) {
                        if (config('settings.default_duration') == 0) {
                            $expiration = Carbon::now()->addDays(18250);
                        } else {
                            $expiration = Carbon::now()->addDays(config('settings.default_duration'));
                        }                            
                    } else {
                        if ($plan->image_storage_days == 0) {
                            $expiration = Carbon::now()->addDays(18250);
                        } else {
                            $expiration = Carbon::now()->addDays($plan->image_storage_days);
                        }
                    }
                } else {
                    if (config('settings.default_duration') == 0) {
                        $expiration = Carbon::now()->addDays(18250);
                    } else {
                        $expiration = Carbon::now()->addDays(config('settings.default_duration'));
                    } 
                }

                $content = new Image();
                $content->user_id = auth()->user()->id;
                $content->name = $request->name;
                $content->description = $request->title;
                $content->resolution = $request->resolution;
                $content->image = $image_url;
                $content->plan_type = $plan_type;
                $content->storage = $storage;
                $content->expires_at = $expiration;
                $content->image_name = 'images/' . $name;
                $content->save();
            }
            
            # Update credit balance
            $this->updateBalance($max_results);

            $data['status'] = 'success';
            $data['old'] = auth()->user()->available_images + auth()->user()->available_images_prepaid;
            $data['current'] = auth()->user()->available_images + auth()->user()->available_images_prepaid - $max_results;
             

        } else {

            $message = $response['error']['message'];

            $data['status'] = 'error';
            $data['message'] = $message;
        }
        echo json_encode($data);
    }
}
