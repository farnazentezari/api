<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Voice;
use App\Models\Language;
use App\Models\VoiceoverLanguage;
use App\Http\Controllers\ApiController;
use App\Services\AzureTTSService;
use App\Services\GCPTTSService;
use Illuminate\Support\Facades\Storage;

// use OpenAI\Laravel\Facades\OpenAI;
use Orhanerday\OpenAi\OpenAi;
use Illuminate\Support\Str;


class VoiceController extends Controller
{
    public function __construct()
    {
        $this->api = new ApiController();
    }
    public function index(){

       

    }
    //getlanguage api
    public function getlanguage(){
        $languages=Language::all();

        $data['status']="sucess";
        $data['data']=$languages;
        echo json_encode($data);
    }
    //getaccountvoice api
    public function getaccountvoice(Request $request){
        $voices=Voice::all()->where(['language_code'=>$request->language_code]);

        $data['status']="sucess";
        $data['data']=$voices;
        echo json_encode($data);
    }
    public function store(Request $request){


        $input = json_decode(request('input_text'), true);
        $length = count($input);

        if ($request->ajax()) {
        
            request()->validate([                
                'title' => 'nullable|string|max:255',
            ]);

            # Count characters based on vendor requirements
            $total_characters = mb_strlen(request('input_text_total'), 'UTF-8');

            # Protection from overusage of credits
            if ($total_characters > env('DAVINCI_SETTINGS_VOICEOVER_MAX_CHAR_LIMIT')) {
                return response()->json(["error" => __("Total characters of your text is more than allowed. Please decrese the length of your text.")], 422);
            }
            

            # Variables for recording
            $total_text = '';
            $total_text_raw = '';
            $total_text_characters = 0;
            $inputAudioFiles = [];
            $plan_type ='free'; 
           
            # Audio Format
            if (request('format') == 'mp3') {
                $audio_type = 'audio/mpeg';
            } elseif(request('format') == 'wav') {
                $audio_type = 'audio/wav';
            } elseif(request('format') == 'ogg') {
                $audio_type = 'audio/ogg';
            } elseif (request('format') == 'webm') {
                $audio_type = 'audio/webm';
            }

            # Process each textarea row
            foreach ($input as $key => $value) {
                $voice_id = explode('___', $key);
                $voice = Voice::where('voice_id', $voice_id[0])->first();
                $language = VoiceoverLanguage::where('language_code', $voice->language_code)->first();
                $no_ssml_tags = preg_replace('/<[\s\S]+?>/', '', $value);

                if ($length > 1) {
                    $total_text .= $voice->voice . ': '. preg_replace('/<[\s\S]+?>/', '', $value) . '. ';
                    $total_text_raw .= $voice->voice . ': '. $value . '. ';
                } else {
                    $total_text = preg_replace('/<[\s\S]+?>/', '', $value) . '. ';
                    $total_text_raw = $value . '. ';
                }


                # Count characters based on vendor requirements
                switch ($voice->vendor) {
                    case 'gcp':               
                            $text_characters = mb_strlen($value, 'UTF-8');
                            $total_text_characters += $text_characters;
                        break;
                    case 'azure':
                            $text_characters = $this->countAzureCharacters($voice, $value);
                            $total_text_characters += $text_characters;
                        break;
                }
                
                # Name and extention of the result audio file
                if (request('format') === 'mp3') {
                    $temp_file_name = Str::random(10) . '.mp3';
                } elseif (request('format') === 'ogg')  {                
                    $temp_file_name = Str::random(10) .'.ogg';
                } elseif (request('format') === 'webm') {
                    $temp_file_name = Str::random(10) .'.webm';
                } elseif (request('format') === 'wav') {
                    $temp_file_name = Str::random(10) .'.wav';
                } else {
                    return response()->json(["error" => __("Unsupported audio file extension was selected")], 422);
                } 


                switch ($voice->vendor) {
                    case 'azure':
                            if (request('format') != 'wav') {
                                $response = $this->processText($voice, $value, request('format'), $temp_file_name);
                            } else {continue 2;}
                        break;
                    case 'gcp':
                            if (request('format') != 'webm') {
                                $response = $this->processText($voice, $value, request('format'), $temp_file_name);
                            } else {continue 2;}
                        break;
                    default:
                        # code...
                        break;
                }


                if ($length == 1) {
       
                    $result_url = Storage::url($temp_file_name);                
                              
                    $result = new VoiceoverResult([
                        'user_id' => 1,
                        'language' => $language->language,
                        'language_flag' => $language->language_flag,
                        'voice' => $voice->voice,
                        'voice_id' => $voice_id[0],
                        'gender' => $voice->gender,
                        'text' => $total_text,
                        'text_raw' => $total_text_raw,
                        'characters' => $text_characters,
                        'file_name' => $temp_file_name,                    
                        'result_ext' => request('format'),
                        'result_url' => $result_url,
                        'title' =>  htmlspecialchars(request('title')),
                        'project' => request('project'),
                        'voice_type' => $voice->voice_type,
                        'vendor' => $voice->vendor,
                        'vendor_id' => $voice->vendor_id,
                        'audio_type' => $audio_type,
                        'storage' => config('settings.voiceover_default_storage'),
                        'plan_type' => $plan_type,
                        'mode' => 'file',
                    ]); 
                        
                    $result->save();

                    $data = [];
                    $data['status'] ="Success! Text was synthesized successfully";
                    return $data;

                } else {

                    array_push($inputAudioFiles, 'storage/' . $response['name']);

                    $result = new VoiceoverResult([
                        'user_id' => 1,
                        'language' => $language->language,
                        'voice' => $voice->voice,
                        'voice_id' => $voice_id[0],
                        'text_raw' => $value,
                        'vendor' => $voice->vendor,
                        'vendor_id' => $voice->vendor_id,
                        'characters' => $text_characters,
                        'voice_type' => $voice->voice_type,
                        'plan_type' => $plan_type,
                        'storage' => env('DAVINCI_SETTINGS_VOICEOVER_DEFAULT_STORAGE'),
                        'mode' => 'hidden',
                    ]); 
                        
                    $result->save();
                }
            }      

            # Process multi voice merge process
            if ($length > 1) {

                # Name and extention of the main audio file
                if (request('format') == 'mp3') {
                    $file_name = Str::random(10) . '.mp3';
                } elseif (request('format') == 'ogg') {
                    $file_name = Str::random(10) .'.ogg';
                } elseif (request('format') == 'wav') {
                    $file_name = Str::random(10) .'.wav';
                } elseif (request('format') == 'webm') {
                    $file_name = Str::random(10) .'.webm';
                } else {
                    return response()->json(["error" => __("Unsupported audio file extension was selected")], 422);
                } 

                $result_url = Storage::url($file_name);                
                

                $result = new VoiceoverResult([
                    'user_id' => Auth::user()->id,
                    'language' => $language->language,
                    'language_flag' => $language->language_flag,
                    'voice' => $voice->voice,
                    'voice_id' => $voice_id[0],
                    'gender' => $voice->gender,
                    'text' => $total_text,
                    'text_raw' => $total_text_raw,
                    'characters' => $total_text_characters,
                    'file_name' => $file_name,
                    'result_url' => $result_url,
                    'result_ext' => request('format'),
                    'title' => htmlspecialchars(request('title')),
                    'project' => request('project'),
                    'voice_type' => 'mixed',
                    'vendor' => $voice->vendor,
                    'vendor_id' => $voice->vendor_id,
                    'storage' => env('DAVINCI_SETTINGS_VOICEOVER_DEFAULT_STORAGE'),
                    'plan_type' => $plan_type,
                    'audio_type' => $audio_type,
                    'mode' => 'file',
                ]); 
                    
                $result->save();

                # Clean all temp audio files
                foreach ($inputAudioFiles as $value) {
                    $name_array = explode('/', $value);
                    $name = end($name_array);
                    if (Storage::disk('audio')->exists($name)) {
                        Storage::disk('audio')->delete($name);
                    }
                }              
                
                $data = [];
                $data['status'] = __("Success! Text was synthesized successfully");
                return $data;

            }
        }
    }
    private function countAzureCharacters(Voice $voice, $text) 
    {
        switch ($voice->language_code) {
            case 'zh-HK':
            case 'zh-CN':
            case 'zh-TW':
            case 'ja-JP':
            case 'ko-KR':
                    $total_characters = mb_strlen($text, 'UTF-8') * 2;
                break;            
            default:
                    $total_characters = mb_strlen($text, 'UTF-8');
                break;
        }

        return $total_characters;
    }
    private function processText(Voice $voice, $text, $format, $file_name)
    {   
        $gcp = new GCPTTSService();
        $azure = new AzureTTSService();
        
        switch($voice->vendor) {
            case 'azure':
                return $azure->synthesizeSpeech($voice, $text, $format, $file_name);
                break;
            case 'gcp':
                return $gcp->synthesizeSpeech($voice, $text, $format, $file_name);
                break;
        }
    }
}
