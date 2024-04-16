<?php declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(Request $request)
    {

        // データ取得
        $products = $this->getProducts();

        return view('dashboard', [
            'products' => $products
        ]);
    }
    
    public function search(Request $request): JsonResponse
    {

        $limit = 10;
        $per_page = 10;
        $page = $request->get('page', 1);
        $sort_column = $request->get('sort', 'pid');
        $sort_direction = $request->get('sort_direction', 'asc');
        $direction = $request->get('direction', 'asc');

        $makers = $request->get('makers', '');
        $types = $request->get('types', '');
        $characters = $request->get('characters', '');
        $param = [
            'makers' => $makers,
            'types' => $types,
            'characters' => $characters,
        ];
        // データ取得
        // $products = $this->searchProducts([], "pid", "asc", 0, 10);
        $products = $this->searchProducts($param, $sort_column, $sort_direction, (($page-1)*$per_page), $limit);

        $products_count = $this->searchProductsCount($param);
        
        // current_page
        $page = $request->get('page', 1);
        $next_page = $page;
        // next, prev 
        $direction = $request->get('direction');
        if (!empty($direction)) {

            if ($direction == 'next') {
                $next_page = $next_page + 1;

            } else if ($direction == 'prev') {
                $next_page = $next_page - 1;
            }

        }

        //$boundary_page = -1;
        $boundary_page = $page-1;
        
        // total_count
        $total_count = $products_count;
        // per_page
        $per_page = 10;
        // last_page
        $last_page = ceil($total_count / $per_page);


        $pagination_type = null;
        if ($total_count <= $per_page * 7) {
            $pagination_type = 'type1';

        } else {
            // 両端のページの場合
            if ($next_page <= 3 || $next_page >= ($last_page-2)) {
                $pagination_type = 'type2_edge';
                
                // 両端ページの境界ページからPrev, Nextの場合
                if ($page == 3 && ($direction == 'next')) {
                    $pagination_type = 'type2_middle';
                    // $boundary_page = 4;
                }
                if ($page == ($last_page-2) && ($direction == 'prev')){
                    $pagination_type = 'type2_middle';
                    // $boundary_page = $last_page -2 -3;
                }

            } else {

                $pagination_type = 'type2_middle';

                // if (empty($direction)) {
                //     $boundary_page = $next_page - 1;

                // } else {
                //     if (($direction == 'next')) {
                //         $boundary_page = $boundary_page + 1;
                //     }
                //     if (($direction == 'prev')){
                //         $boundary_page = $boundary_page - 1;
                //     }
    
                // }


            }

        }

        //dd($next_page, $pagination_type, $boundary_page);

        // return view('products', [
        //     'products' => $products,
        //     'current_page' => $next_page,
        //     'total_count' => $total_count,
        //     'per_page' => $per_page,
        //     'last_page' => $last_page,
        //     'pagination_type' => $pagination_type,
        //     'boundary_page' => $boundary_page,
        // ]);

        //dd($request);
        
        return new JsonResponse([
            'data' => [
                'makers' => $makers,
                // 'auth' => auth()->user(),
                // 'auth2' => Auth::user(),
                'page' => $request->input('page'),
                'direction' => $request->input('direction'),
                'conditions' => $request->input('conditions'),
                'products' => $products,
                'current_page' => $next_page,
                'total_count' => $total_count,
                'per_page' => $per_page,
                'last_page' => $last_page,
                'pagination_type' => $pagination_type,
                'boundary_page' => $boundary_page,
            ],
        ]);

    }

    public function getProductDetail(Request $request): JsonResponse
    {
        $id = $request->id;

        // データ取得
        $product = $this->getProducts(['pid'=>$id]);
        $jancode = $product[0]->jancode;
        
        // 画像ファイル取得
        $imageFileNames = $this->getImageFileNames($jancode);

        return new JsonResponse([
            'data' => [
                'product' => $product,
                'imageNames' => $imageFileNames,
            ],
        ]);
        
    }
    

    private function getImageFileNames($jancode) : array 
    {
        // $files = Storage::disk('local')->files('/public/images/products/1');
        // $files = Storage::disk('front_images')->files('/products/{$pid}');
        
        // 開発環境
        $imageDirectory = public_path('images/products/' . $jancode);
        // 本番環境
        // シンボリックリンク経由だとアクセス不可
        //$imageDirectory = public_path('front_images/products/' . $pid);
        // 直接指定
        //$imageDirectory = '/home/c2655480/public_html/tokyoworkswd.com/images/products/' . $pid;
        $filenames = [];

        if (File::exists($imageDirectory)) {
            
            // thumbnail.jpsは除外する
            // Get all files in the directory
            $files = File::files($imageDirectory);

            $files = array_filter($files, function($file) {
                return ($file->getFilename()) != 'thumbnail.jpg';
            });

            $filenames = array_map(function ($file) {
                return $file->getFilename();
            }, $files);
        
        }
        
        return $filenames;
        //dd($filenames);
        // dd(Storage::allFiles('/var/www/html/public/images/products/1'));
        // dd(public_path().'/images/products/'.$pid);
        // dd(Storage::allFiles(public_path().'/images/products/'.$pid));

        // return [
        //     'image1.jpg',
        //     'image2.jpg',
        //     'image3.jpg',
        // ];
    }

    public function uploadProducts(Request $request)
    {

        $csvData = $this->loadProductsCsv($request->products_file);
        DB::table('product')->upsert($csvData,['pid']);

        // データ取得
        $products = $this->getProducts();

        return view('dashboard', [
            'products' => $products
        ]);

    }

    public function uploadCategories(Request $request)
    {

        $csvData = $this->loadCategoriesCsv($request->products_file);
        foreach ($csvData as $pid=>$product) {

            DB::table('product_category')->where('pid',$pid)->delete();
            if (empty($product)) continue;
            
            foreach ($product as $cid=>$cseqs) {
                
                $insertData = null;
                foreach($cseqs as $key=>$cseq){
                    $insertData[] = [
                        'pid' => $pid,
                        'cid' => $cid,
                        'cseq' => $cseq,
                    ];
                }
                if (count($insertData)>0) {
                    DB::table('product_category')->insert($insertData);
                }

            }

        }

        // データ取得
        $products = $this->getProducts();

        return view('dashboard', [
            'products' => $products
        ]);

    }


    private function loadProductsCsv($file) {

        $oldFiles = Storage::allFiles('public/csv/');
        foreach ( $oldFiles as $oldFile ) {
            Storage::delete($oldFile);
        }
        //ファイルの保存
        $newCsvFileName = $file->getClientOriginalName();
        $file->storeAs('public/csv', $newCsvFileName);
        
        //保存したCSVファイルの取得
        $csv = Storage::disk('local')->get("public/csv/{$newCsvFileName}");
        // OS間やファイルで違う改行コードをexplode統一
        //$csv = str_replace(array("\r\n", "\r"), "\n", $csv);
        // $csvを元に行単位のコレクション作成。explodeで改行ごとに分解
        //$csvData = collect(explode("\n", $csv));
        //$csvData = collect(explode("\r\n", $csv));

        // CSV情報の取得
        $csv_content = new \SplFileObject(storage_path('app/public/csv/'.$newCsvFileName));
 
        $csv_content->setFlags(
            \SplFileObject::READ_CSV |      // CSVとして行を読み込み
            \SplFileObject::READ_AHEAD |    // 先読み／巻き戻しで読み込み
            \SplFileObject::SKIP_EMPTY |    // 空行を読み飛ばす
            \SplFileObject::DROP_NEW_LINE   // 行末の改行を読み飛ばす
        );        

        // 配列に変換
        $csv_data = [];

        foreach($csv_content as $value) {

            // 文字コード変換
            $value = mb_convert_encoding($value, "UTF-8");

            // 先頭行（項目行）を省く
            if(strpos($value[0], 'ID')){
                continue;
            }

            $csv_data[] = [
                'pid' => $value[0],
                'jancode' => $value[1],
                'name' => $value[2],
                'description' => $value[3],
            ];
            
        }

        //dd($csv_data);

        return $csv_data;
    }

    
    private function loadCategoriesCsv($file) {

        $oldFiles = Storage::allFiles('public/csv/');
        foreach ( $oldFiles as $oldFile ) {
            Storage::delete($oldFile);
        }
        //ファイルの保存
        $newCsvFileName = $file->getClientOriginalName();
        $file->storeAs('public/csv', $newCsvFileName);
        
        //保存したCSVファイルの取得
        $csv = Storage::disk('local')->get("public/csv/{$newCsvFileName}");
        // CSV情報の取得
        $csv_content = new \SplFileObject(storage_path('app/public/csv/'.$newCsvFileName));
 
        $csv_content->setFlags(
            \SplFileObject::READ_CSV |      // CSVとして行を読み込み
            \SplFileObject::READ_AHEAD |    // 先読み／巻き戻しで読み込み
            \SplFileObject::SKIP_EMPTY |    // 空行を読み飛ばす
            \SplFileObject::DROP_NEW_LINE   // 行末の改行を読み飛ばす
        );

        // 配列に変換
        $csv_data = [];
        $product_id = '';
        foreach($csv_content as $value) {

            // 文字コード変換
            $value = mb_convert_encoding($value, "UTF-8");

            // 先頭行（項目行）を省く
            if(strpos($value[0], 'PRODUCT_ID')){
                continue;
            }

            $product_id = $value[0];
            $category_id = $value[1];
            $category_seq = $value[2];
            if(empty($product_id)){
                continue;
            }

            // product_idのみ記入された場合は、データ削除対象
            if (empty($category_id) || empty($category_seq)) {
                $csv_data[$product_id] = null;

            } else {

                if ( array_key_exists($product_id, $csv_data) &&
                     array_key_exists($category_id, $csv_data[$product_id]) ) {
                    $csv_data[$product_id][$category_id][] = $category_seq;

                } else {
                    $csv_data[$product_id][$category_id] = [$category_seq];

                }

            }

            
        }

        //dd($csv_data);

        return $csv_data;
    }

    // CSV 
    // メーカー

    private function getProducts($params=null) {
        // データ取得
        $query = DB::table('products')
            ->select('products.pid','products.name as pname', 
                'products.release_date','products.deadline_date', 'products.product_number', 'products.jancode', 
                'products.qty_per_carton','products.qty_per_inner_carton', 'products.moq', 'products.base_price', 
                'products.status','products.remarks', 
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



    /**
     * searchProducts
     */
    private function searchProducts($params, $sort_column, $sort_direction, $offset, $limit) {
        // データ取得
        $query = DB::table('products')
            ->select('products.pid','products.name as pname', 'products.jancode', 'products.base_price', 
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
            ->groupBy('products.pid', 'products.name', 'products.jancode');



        $categorySubquery = "";
        if ($params['makers']??null) {
            $categorySubquery = "SELECT pid FROM product_categories WHERE cid = 1 AND cseq IN (" .$params['makers']. ")";

        }
        
        if ($params['types']??null) {
            $categorySubquery = $categorySubquery . ((strlen($categorySubquery)>0) ? " UNION ": "");
            $categorySubquery = $categorySubquery . "SELECT pid FROM product_categories WHERE cid = 2 AND cseq IN (" .$params['types']. ")";
        }

        if ($params['characters']??null) {
            $categorySubquery = $categorySubquery . ((strlen($categorySubquery)>0) ? " UNION ": "");
            $categorySubquery = $categorySubquery . "SELECT pid FROM product_categories WHERE cid = 3 AND cseq IN (" .$params['characters']. ")";
        }

        if (strlen($categorySubquery)>0) {
            $query->whereRaw('products.pid IN ('.$categorySubquery.')');
            // $query->whereRaw('products.pid IN (SELECT pid FROM product_categories WHERE cid = 1 UNION SELECT pid FROM product_categories WHERE cid = 3)');
        }

        $products = $query->offset($offset)
                ->limit($limit)
                ->orderBy($sort_column, $sort_direction)
                ->get();

        //dd($products);
        return $products;
        
    }
    /**
     * searchProducts
     */
    private function searchProductsCount($params) {
        // データ取得
        $query = DB::table('products')
            ->select('products.pid','products.name as pname', 'products.jancode', 'products.base_price', 
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
            ->groupBy('products.pid', 'products.name', 'products.jancode');



        $categorySubquery = "";
        if ($params['makers']??null) {
            $categorySubquery = "SELECT pid FROM product_categories WHERE cid = 1 AND cseq IN (" .$params['makers']. ")";

        }
        
        if ($params['types']??null) {
            $categorySubquery = $categorySubquery . ((strlen($categorySubquery)>0) ? " UNION ": "");
            $categorySubquery = $categorySubquery . "SELECT pid FROM product_categories WHERE cid = 2 AND cseq IN (" .$params['types']. ")";
        }

        if ($params['characters']??null) {
            $categorySubquery = $categorySubquery . ((strlen($categorySubquery)>0) ? " UNION ": "");
            $categorySubquery = $categorySubquery . "SELECT pid FROM product_categories WHERE cid = 3 AND cseq IN (" .$params['characters']. ")";
        }

        if (strlen($categorySubquery)>0) {
            $query->whereRaw('products.pid IN ('.$categorySubquery.')');
            // $query->whereRaw('products.pid IN (SELECT pid FROM product_categories WHERE cid = 1 UNION SELECT pid FROM product_categories WHERE cid = 3)');
        }
        
        $products = $query->get();

        //dd(count($products));
        return count($products);
        
    }

    /**
     * detail
     */
    public function detail(Request $request)
    {
        $id = $request->id;

        
        // データ取得
        //$products = $this->getProducts();

        return view('product', []);
        
    }

}
