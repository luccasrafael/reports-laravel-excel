<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $response = Http::post('http://demo.blocworx.local/api/authenticate', [
            'email' => $request->email,
            'password' => $request->password
        ]);

        $this->token = $response->json()['token'];

        return response()->json($response->json());
    }

    public function getHome(Request $request)
    {
        try {
            $response = Http::withToken(env('API_TOKEN'))->get('http://demo.blocworx.local/api/api/V2/home');
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }

        return response($response);
        return response()->json( $response->json() );
    }
}
