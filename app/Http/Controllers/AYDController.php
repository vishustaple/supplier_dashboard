<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AYDController extends Controller
{
    public function createSession(Request $request)
    {
        $apiKey = env('AYD_API_KEY');
        $chatbotId = env('AYD_CHATBOT_ID');

        // Pull user details (from session or auth middleware)
        $user = auth()->user(); // or customize based on your setup

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->post('https://www.askyourdatabase.com/api/chatbot/v2/session', [
            'chatbotid' => $chatbotId,
            'name'      => $user->name ?? 'Guest',
            'email'     => $user->email ?? 'guest@example.com',
        ]);

        return response()->json($response->json());
    }
}
