<?php declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;


final class LoginController extends Controller
{
    private $auth;
    const SESSION_KEY_INQUIRY = 'inquiry';
    const SESSION_KEY_TOTAL_PRICE = 'total_price';

    /**
     * @param AuthManager $auth
     */
    public function __construct(AuthManager $auth) 
    {
        $this->auth = $auth;
    }
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @throws AuthenticationException
     */
    // public function __invoke(LoginRequest $request): JsonResponse
    public function login(LoginRequest $request): JsonResponse
    {

        $credentials = $request->only(['email', 'password']);

        if ($this->auth->guard()->attempt($credentials)) {
            $request->session()->regenerate();
            $user = $request->user();

            $data = DB::table('users')
                ->select('users.id', 'users.name', 'users.email', 'users.lid', 'price_layers.rate as rate')
                ->leftJoin('price_layers', 'users.lid', '=', 'price_layers.lid')
                ->where('users.id', $user->id)
                ->first();
            
            // Remove session data
            Session::forget(self::SESSION_KEY_INQUIRY);
            Session::forget(self::SESSION_KEY_TOTAL_PRICE);
                            
            return new JsonResponse([
                'message' => 'Authenticated.',
                'data' => $data,
            ]);
        }

        throw new AuthenticationException();
    }

    // public function guestLogin(LoginRequest $request): JsonResponse
    // {

    //     $credentials = $request->only(['email', 'password']);

    //     if ($this->auth->guard()->attempt($credentials)) {
    //         $request->session()->regenerate();
    //         $user = $request->user();
    //         $data = DB::table('users')
    //             ->select('users.id', 'users.name', 'users.email', 'users.lid', 'price_layers.rate as rate')
    //             ->leftJoin('price_layers', 'users.lid', '=', 'price_layers.lid')
    //             ->where('users.id', $user->id)
    //             ->first();

    //         return new JsonResponse([
    //             'message' => 'Authenticated.',
    //             'data' => $user,
    //         ]);
    //     }

    //     throw new AuthenticationException();
    // }



}
