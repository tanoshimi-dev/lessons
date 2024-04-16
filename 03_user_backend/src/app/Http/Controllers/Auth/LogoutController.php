<?php declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LogoutController extends Controller
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
     */
    public function __invoke(Request $request): JsonResponse
    {
        if ($this->auth->guard()->guest()) {
            return new JsonResponse([
                'message' => 'Already Unauthenticated.',
            ]);
        }

        $this->auth->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Remove session data
        Session::forget(self::SESSION_KEY_INQUIRY);
        Session::forget(self::SESSION_KEY_TOTAL_PRICE);

        return new JsonResponse([
            'message' => 'Unauthenticated.',
        ]);
    }
}
