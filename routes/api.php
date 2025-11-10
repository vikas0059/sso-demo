<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthenticationController;
use App\Http\Controllers\Api\VerificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

    Route::post('register', [AuthenticationController::class, 'register']);
    Route::post('login', [AuthenticationController::class, 'login']);

    Route::middleware(['auth:api'])->group(function(){
        Route::post('logout', [AuthenticationController::class, 'destroy']);

        Route::post('/email/verification-notification', function (Request $request) {
            $user = $request->user('api');

            if($user->hasVerifiedEmail){
                return response()->json(['message' => 'Email already verified'], 200);
            }

            $user->sendEmailVerificationNotification();
    
            return response()->json(['message', 'Verification link sent successfully!'], 200);
        })->middleware(['auth', 'throttle:6,1'])->name('verification.send'); 
    });

    Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
         ->middleware(['auth:api', 'signed']) 
         ->name('verification.verify');

    


