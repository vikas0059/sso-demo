<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use GuzzleHttp\Client;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Gate;

class AuthenticationController extends Controller
{

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        
        $user->sendEmailVerificationNotification();
        
        $token = $user->createToken('auth_token')->accessToken;

        // event(new Registered($user)); 

        return response()->json([
        'message' => 'User Registered Successfully. Verification email sent.',
        'user' => $user,
        'access_token' => $token,
    ]);

    }

    public function login(Request $request)
    {
            if (Auth::attempt(['email' => request('email'), 'password' => request('password')])) {
                // successfull authentication
                $user = Auth::user();

                if(Gate::allows('is-verfied', $user)){
                    $http = new Client;

                    $response = $http->post('http://localhost:8001/oauth/token', [
                        'form_params' => [
                        'grant_type' => 'password',
                        'client_id' => env('PASSPORT_PASSWORD_CLIENT_ID'),
                        'client_secret' => env('PASSPORT_PASSWORD_SECRET'),
                        'username' => request('email'),
                        'password' => request('password'),
                        'scope' => '',
                        ]
                    ]);

                    $user['token'] = json_decode((string) $response->getBody(), true);
                    return response()->json([
                        'success' => true,
                        'statusCode' => 200,
                        'message' => 'User has been logged successfully.',
                        'data' => $user,
                    ], 200);

                }else{
                    return response()->json(['message' => 'Please complete email verification first!'], 401);
                }

            } else {
                // failure to authenticate
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to authenticate.',
                ], 401);
            }

    }

    public function destroy(Request $request)
    {
        if(Auth::user()) {
            $request->user()->token()->revoke();

            return response()->json([
                'success' => true,
                'code' => 'Logged out successfully',
            ], 200);
        }

    }
}
