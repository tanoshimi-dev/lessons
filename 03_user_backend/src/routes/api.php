<?php declare(strict_types=1);

use App\Http\Controllers\Api\HelloController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\InquiryController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\MailController;

use App\Http\Controllers\Api\MeController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::get('/hello', HelloController::class);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/me', MeController::class);
    Route::post('/user/update', [UserController::class, 'update']);
    Route::get('/inquiry/add/{id}/{quantity}', [InquiryController::class, 'addProductInquiry']);
    Route::get('/inquiry/list', [InquiryController::class, 'getInquiryList']);

});


Route::get('/user/get-is-logged-in', [UserController::class, 'isLoggedIn']);
Route::post('/user/authenticated', [UserController::class, 'getAuthenticatedUser']);
Route::get('/user/profile', [UserController::class, 'getAuthenticatedUser']);



Route::get('/users', [UserController::class, 'users']);
Route::get('/products', [ProductController::class, 'search']);
Route::get('/product/{id}', [ProductController::class, 'getProductDetail']);

// Categories
Route::get('/categories', [CategoryController::class, 'getAllCategories']);
Route::get('/categories/{cid}', [CategoryController::class, 'getCategories']);
Route::get('/makerlogos', [CategoryController::class, 'getMakerLogos']);

// News
Route::get('/news', [NewsController::class, 'news']);

// Mail
Route::post('/mail/send', [MailController::class, 'sendMail']);
