<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Storage;
use Avatar;
use App\Notifications\SignupActivate;
use App\Notifications\SignupActivated;
use App\User;
use Mail;

class AuthController extends Controller
{
    /**
     * Create user deactivate and send notification to activate account user
     *
     * @param  [string] name
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @return [string] message
     */
    public function signup(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|confirmed'
        ]);

        $initialPass = str_random(8);
        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($initialPass),
            // 'activation_token' => str_random(60)
        ]);

        $user->save();
        $from = "test@us.com";
        $to = $request->email;
        $subject = "password";
        $message = $initialPass;
        $headers = "From:" . $from;
        mail($to,$subject,$message, $headers);


        $avatar = Avatar::create($user->name)->getImageObject()->encode('png');
        Storage::put('avatars/'.$user->id.'/avatar.png', (string) $avatar);

        // $user->notify(new SignupActivate($user));

        return response()->json([
            'message' => __('auth.signup_success')
        ], 201);
    }

    /**
     * Confirm your account user (Activate)
     *
     * @param  [type] $token
     * @return [string] message
     * @return [obj] user
     */
    // public function signupActivate($token)
    // {
    //     $user = User::where('activation_token', $token)->first();
    //
    //     if (!$user) {
    //         return response()->json([
    //             'message' => __('auth.token_invalid')
    //         ], 404);
    //     }
    //
    //     $user->active = true;
    //     $user->activation_token = '';
    //     $user->save();
    //
    //     $user->notify(new SignupActivated($user));
    //
    //     return $user;
    // }

    /**
     * Login user and create token
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [boolean] remember_me
     * @return [string] access_token
     * @return [string] token_type
     * @return [string] expires_at
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember_me' => 'boolean'
        ]);

        // $email = request['email'];
        $email = $request->email;
        $check = User::where('email', $email)->value('active');

        if ($check === 0){
          return $check;
        }else{

        $credentials = request(['email', 'password']);
        $credentials['active'] = 1;
        $credentials['deleted_at'] = null;

        if(!Auth::attempt($credentials))
            return response()->json([
                'message' => __('auth.login_failed'),
                'error' => 'true'
            ], 401);

        $user = $request->user();

        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;

        if ($request->remember_me)
            $token->expires_at = Carbon::now()->addWeeks(1);

        $token->save();

        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse($tokenResult->token->expires_at)->toDateTimeString(),
            'error' => 'false'
        ]);
      }
    }

    /**
     * Logout user (Revoke the token)
     *
     * @return [string] message
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'message' => __('auth.logout_success')
        ]);
    }

    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
