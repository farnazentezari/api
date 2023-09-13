<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// use App\Models\Codes;
use App\Http\Controllers\ApiController;
// use OpenAI\Laravel\Facades\OpenAI;
use Orhanerday\OpenAi\OpenAi;



class TuneController extends Controller
{
    public function __construct()
    {
        $this->api = new ApiController();
    }
    public function store(Request $request){

       
        
    }
}
