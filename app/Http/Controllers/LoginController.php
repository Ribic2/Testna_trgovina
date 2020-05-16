<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Foundation\Auth\ThrottlesLogins;

class LoginController extends Controller
{

    use ThrottlesLogins;
    /**
     * Function that sets username to email, so login throttling will work
     */
    public function username()
    {
        return 'email';
    }
    /**
     * Login function that authenticates user
     */
    public function login(Request $request){



        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if($this->hasTooManyLoginAttempts($request)){
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }




        if(Auth::attempt($credentials)){
            $this->clearLoginAttempts($request);
            //return response()->json(['authentication' => true]);

            $user = User::find(1);

            $accessToken = $user->createToken('accessToken')->accessToken;

            return response(['user'=>Auth::user(), 'access_token'=> $accessToken, 'authentication' => true]);


        }
        else{
            $this->incrementLoginAttempts($request);
            return response()->json(['authentication' => false]);
        }



    }
}