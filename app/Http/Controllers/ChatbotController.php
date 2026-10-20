<?php

namespace App\Http\Controllers;

use App\Support\LibraryChatbot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Library Assistant chat endpoint. Open to everyone (public catalog visitors included),
 * but loan / reservation answers are only given to the signed-in patron about themselves.
 * Read only: the bot never changes any record.
 */
class ChatbotController extends Controller
{
    public function message(Request $request, LibraryChatbot $bot): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:300'],
        ]);

        return response()->json($bot->reply($data['message'], $request->user()));
    }
}
