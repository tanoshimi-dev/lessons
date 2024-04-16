<?php declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getAuthenticatedUser(Request $request): JsonResponse
    {
        $user = $request->user();
        //dd($user);

        return new JsonResponse([
            'authcheck' => Auth::check(),
            'data' => $user,
        ]);

    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function users(Request $request): JsonResponse
    {

        $id = $request->input('id');
        $users_query = DB::table('users');
        if (!empty($id)) {
            $users_query = $users_query->where('id', $id);
        }

        $users = $users_query->orderBy('id', 'desc')->get();
        
        return new JsonResponse([
            'data' => $users,
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {

        $id = $request->input('id');
        $name = $request->input('name');
        $email = $request->input('email');

        $update_data = [];
        if (!empty($name)) {
            $update_data['name'] = $name;
        }
        if (!empty($email)) {
            $update_data['email'] = $email;
        }

        if (!empty($update_data)) {
            $result = DB::table('users')
                ->where('id', $id)
                ->update($update_data);
        }

        $users = DB::table('users')->where('id', $id)->get();
        
        return new JsonResponse([
            'data' => $users,
        ]);
    }
}

