<?php declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

use Illuminate\Http\JsonResponse;

class InquiryController extends Controller
{

    const SESSION_KEY_INQUIRY = 'inquiry';
    const SESSION_KEY_TOTAL_PRICE = 'total_price';

    public function addProductInquiry(Request $request, $product_id, $quantity): JsonResponse
    {

        $user = $request->user();
        if (empty($user)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'ログインしてください。',
            ]);
        }
        $user = DB::table('users')
            ->select('users.id', 'users.name', 'users.email', 'users.lid', 'price_layers.rate as rate')
            ->leftJoin('price_layers', 'users.lid', '=', 'price_layers.lid')
            ->where('users.id', $user->id)
            ->first();
        
        $price_rate = 1;
        if ($user->rate) {
            $price_rate = $user->rate;
        }

        // TODO IDが存在するかチェック, 数量が正しいかチェック
        $product = $this->getProducts(['pid'=>$product_id]);
        if (empty($product)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => '商品が見つかりません。',
            ]);
        }

        //$request->session()->push(self::SESSION_KEY, $product_id);
        $inquiry_list = $request->session()->get(self::SESSION_KEY_INQUIRY, []);
        if(key_exists($product_id, $inquiry_list)){
            $current_quantity = $inquiry_list[$product_id]['quantity'];
            $inquiry_list[$product_id]['quantity'] = $current_quantity + $quantity;
        } else {
            $inquiry_list[$product_id] = [
                'id' => $product_id,
                'name' => $product[0]->pname,
                'base_price' => $product[0]->base_price,
                'product_number' => $product[0]->product_number,
                'jancode' => $product[0]->jancode,
                'quantity' => $quantity,
            ];
        }
        $request->session()->put(self::SESSION_KEY_INQUIRY, $inquiry_list);

        // 合計金額
        $total_price = $request->session()->get(self::SESSION_KEY_TOTAL_PRICE, 0);
        $current_price = ($product[0]->base_price) * $price_rate * $quantity;
        $request->session()->put(self::SESSION_KEY_TOTAL_PRICE, $total_price + $current_price);

        // データ取得
        //$products = $this->getProducts();

        return new JsonResponse([
            'product_id' => $product_id,
            'quantity' => $quantity,
            'list' => $inquiry_list,
            //'product' => $product,
        ]);

    }
    
    public function getInquiryList(Request $request): JsonResponse
    {

        $user = $request->user();
        if (empty($user)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'ログインしてください。',
            ]);
        }

        $inquiry_list = $request->session()->get(self::SESSION_KEY_INQUIRY, []);
        $total_price = $request->session()->get(self::SESSION_KEY_TOTAL_PRICE, 0);

        return new JsonResponse([
            'list' => $inquiry_list,
            'total_price' => $total_price,
        ]);

    }
    

    public function getProductDetail(Request $request): JsonResponse
    {
        $id = $request->id;

        // データ取得
        $product = $this->getProducts(['pid'=>$id]);

        return new JsonResponse([
            'data' => [
                'product' => $product,
                'imageNames' => [
                    'image1.jpg',
                    'image2.jpg',
                    'image3.jpg',
                ],
            ],
        ]);
        
    }
    

 
    // CSV 
    // メーカー
    private function getProducts($params=null) {
        // データ取得
        $query = DB::table('products')
            ->select('products.pid','products.name as pname', 'products.jancode', 
                'products.product_number', 'products.base_price', 
                DB::raw('GROUP_CONCAT(distinct makers.name) as makers'),
                DB::raw('GROUP_CONCAT(distinct product_type.name) as product_types'),
                DB::raw('GROUP_CONCAT(distinct material.name) as materials'),
            )
            // メーカー
            ->leftJoin('product_category AS cat1', function ($join) {
                $join->where('cat1.cid', '=', 1)
                    ->on('cat1.pid', '=', 'products.pid');
            })
            ->leftJoin('category AS makers', function ($join) {
                $join->on('makers.cid', '=', 'cat1.cid')
                    ->on('makers.cseq', '=', 'cat1.cseq');
            })
            // 商品種別
            ->leftJoin('product_category AS cat2', function ($join) {
                $join->where('cat2.cid', '=', 2)
                    ->on('cat2.pid', '=', 'products.pid');
            })
            ->leftJoin('category AS product_type', function ($join) {
                $join->on('product_type.cid', '=', 'cat2.cid')
                    ->on('product_type.cseq', '=', 'cat2.cseq');
            })
            // 商品種別
            ->leftJoin('product_category AS cat3', function ($join) {
                $join->where('cat3.cid', '=', 3)
                    ->on('cat3.pid', '=', 'products.pid');
            })
            ->leftJoin('category AS material', function ($join) {
                $join->on('material.cid', '=', 'cat3.cid')
                    ->on('material.cseq', '=', 'cat3.cseq');
            });
        
        if (!empty($params)) {
            if (!empty($params['pid'])) {
                $query = $query->where('products.pid', $params['pid']);
            }
            // if (!empty($params['name'])) {
            //     $query = $query->where('product.name', 'like', '%'.$params['name'].'%');
            // }
            // if (!empty($params['jancode'])) {
            //     $query = $query->where('product.jancode', 'like', '%'.$params['jancode'].'%');
            // }
            // if (!empty($params['makers'])) {
            //     $query = $query->where('makers.name', 'like', '%'.$params['makers'].'%');
            // }
            // if (!empty($params['product_types'])) {
            //     $query = $query->where('product_type.name', 'like', '%'.$params['product_types'].'%');
            // }
            // if (!empty($params['materials'])) {
            //     $query = $query->where('material.name', 'like', '%'.$params['materials'].'%');
            // }
        }

        $query = $query->groupBy('products.pid', 'products.name', 'products.jancode');

        $products = $query->get();

        //dd($products);
        return $products;
        
    }




}
