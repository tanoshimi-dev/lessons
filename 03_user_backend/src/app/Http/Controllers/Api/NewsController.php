<?php declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

class NewsController extends Controller
{
    public function news(Request $request)
    {

        // データ取得
        $news = $this->getNews();

        return new JsonResponse([
            'data' => $news,
        ]);

    }
    
    private function getNews() {
        // データ取得
        $news = DB::table('news')
            ->offset(0)
            ->limit(3)
            ->orderBy('publish_date', 'desc')
            ->get();

        // foreach($news as $product) {
        //     $product->base64_image = $this->getBase64Image(storage_path("app/public/news/{$product->nid}/news.png"));
        // }

        return $news;
        
    }

    private function getBase64Image($path) {
        if(!file_exists($path)) return null;
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }

}
