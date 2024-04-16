<?php declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;


class MailController extends Controller
{
    const SESSION_KEY_INQUIRY = 'inquiry';
    const SESSION_KEY_TOTAL_PRICE = 'total_price';

    public function sendMail(Request $request)
    {

        // データ取得
        $list = $request->input('list');
        $ids = $list['id'];
        $productNames = $list['name'];
        $jans = $list['jancode'];
        $prices = $list['base_price'];
        $quantities = $list['quantity'];
        $subtotals = $list['subtotal'];

        $name = $request->input('name');
        $email = $request->input('email');
        $company = $request->input('company');
        $inquiry = $request->input('inquiry');
        $postalCode = $request->input('postal-code');
        $address = $request->input('address');

        //$rate = $request->input('rate');
        $totalPrice = $request->input('totalPrice');

        $details = [];
        foreach ($ids as $index=>$value){
            // $details[] = $productNames[$index] . " (jancode:" . $jans[$index] . ") $" . $prices[$index]  ." x " . $quantities[$index] . " x " .$rate . "(rate) = $" . $subtotals[$index];
            $details[] = $jans[$index] . "," . $quantities[$index];
        }

        $sendResult = false;

        // メール送信
        try {
            Mail::send('mail',[
                'name' => $name,
                'email' => $email,
                'company' => $company,
                'inquiry' => $inquiry,
                'postalCode' => $postalCode,
                'address' => $address,
                'rate' => "",
                'totalPrice' => $totalPrice,
                'details' => $details,
            ], function ($message) use($email, $name){
                // $message->to('example@example.com', 'Example')
                $message->to($email, $name)
                        ->bcc('contact@admin.tokyoworkswd.com', 'TowkyoWorks')
                        ->subject('Thank you for inquiry! - TowkyoWorks');
            });
            $sendResult = true;

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ]);
        }

        // Remove session data
        Session::forget(self::SESSION_KEY_INQUIRY);
        Session::forget(self::SESSION_KEY_TOTAL_PRICE);

        return new JsonResponse([
            'data' => $sendResult
        ]);

    }

}
