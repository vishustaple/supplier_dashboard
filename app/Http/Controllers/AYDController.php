<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class AYDController extends Controller
{
    public function __construct(){
        $this->middleware('permission:Ask Your Database')->only(['index', 'createSession']);
    }

    public function index(){
        $pageTitle = 'Ask Your Database';
        return view('admin.ayd', ['pageTitle' => $pageTitle]);
    }

    public function createSession(Request $request)
    {
        $apiKey = env('AYD_API_KEY');
        $chatbotId = env('AYD_CHATBOT_ID');

        // Optional: Log the API key and bot ID (check .env is loading correctly)
        // dd('Using API Key: ' . $apiKey);
        // dd('Using Chatbot ID: ' . $chatbotId);

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Accept'        => 'application/json, text/plain, */*',
            'Content-Type'  => 'application/json',
        ])->post('https://www.askyourdatabase.com/api/chatbot/v2/session', [
            'chatbotid' => $chatbotId,
            'name'      => $user->name ?? 'Ankit',
            'email'     => $user->email ?? 'ankit@centerpointgroup.com',
        ]);

        // Log the full response for debugging
        // dd('AYD Response: ' . json_encode($response->json()));

        // Return full response to frontend
        return response()->json($response->json(), $response->status());
    }
}
