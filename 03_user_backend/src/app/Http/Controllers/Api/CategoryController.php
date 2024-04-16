<?php declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

final class CategoryController extends Controller
{

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllCategories(): JsonResponse {

        // データ取得
        $categoryData = $this->getAllCategoriesData();
        //dd($products);
        //return $products;
        return new JsonResponse([
            'data' => $categoryData,
        ]);
        
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getCategories($cid): JsonResponse {

        // データ取得
        $categoryData = $this->getCategoryData($cid);
        //dd($products);
        //return $products;
        return new JsonResponse([
            'data' => $categoryData,
        ]);
        
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getCategory($cid, $cseq): JsonResponse {

        // データ取得
        $categoryData = $this->getCategoryData($cid, $cseq);
        //dd($products);
        //return $products;
        return new JsonResponse([
            'data' => $categoryData,
        ]);
        
    }



    private function getProducts() {
        // データ取得
        $products = DB::table('products')
            ->select('products.pid','products.name as pname', 
            'products.release_date','products.deadline_date', 'products.product_number', 
            'products.jancode','products.qty_per_carton', 'products.qty_per_inner_carton', 
            'products.moq','products.base_price', 'products.status', 'products.remarks', 
                DB::raw('GROUP_CONCAT(distinct makers.name) as makers'),
                DB::raw('GROUP_CONCAT(distinct product_type.name) as product_types'),
                DB::raw('GROUP_CONCAT(distinct material.name) as materials'),
            )
            // メーカー
            ->leftJoin('product_categories AS cat1', function ($join) {
                $join->where('cat1.cid', '=', 1)
                    ->on('cat1.pid', '=', 'products.pid');
            })
            ->leftJoin('categories AS makers', function ($join) {
                $join->on('makers.cid', '=', 'cat1.cid')
                    ->on('makers.cseq', '=', 'cat1.cseq');
            })
            // 商品種別
            ->leftJoin('product_categories AS cat2', function ($join) {
                $join->where('cat2.cid', '=', 2)
                    ->on('cat2.pid', '=', 'products.pid');
            })
            ->leftJoin('categories AS product_type', function ($join) {
                $join->on('product_type.cid', '=', 'cat2.cid')
                    ->on('product_type.cseq', '=', 'cat2.cseq');
            })
            // 商品種別
            ->leftJoin('product_categories AS cat3', function ($join) {
                $join->where('cat3.cid', '=', 3)
                    ->on('cat3.pid', '=', 'products.pid');
            })
            ->leftJoin('categories AS material', function ($join) {
                $join->on('material.cid', '=', 'cat3.cid')
                    ->on('material.cseq', '=', 'cat3.cseq');
            })
            ->groupBy('products.pid', 'products.name', 'products.jancode')
            ->get();

        //dd($products);
        return $products;
        
    }

    private function getAllCategoriesData() {
        // データ取得
        $query = DB::table('categories')
            ->select('categories.cid','categories.cseq','categories.name',
                'categories.order','categories.created_at');
        
        $categoryData = $query->orderBy('order')->orderBy('cseq')->get();
        return $categoryData;
        
    }


    private function getCategoryData($cid, $cseq=null) {
        // データ取得
        $query = DB::table('categories')
            ->select('categories.cid','categories.cseq','categories.name',
                'categories.order','categories.created_at')
            ->where('categories.cid', $cid);
        
        if(!empty($cseq)) {
            $query->where('categories.cseq', $cseq);
        }
        
        $categoryData = $query->orderBy('order')->orderBy('cseq')->get();
        //dd($products);
        return $categoryData;
        
    }


   /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getMakerLogos(): JsonResponse {

        // データ取得
        $makersData = $this->getCategoryData(1);
        //dd($makersData);
        //return $products;
        return new JsonResponse([
            'data' => $makersData,
        ]);
        
    }

}

