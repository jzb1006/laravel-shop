<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductsController extends Controller
{
    //
    public function index(Request $request,CategoryService $categoryService){
//        $builder = Product::query()->where('on_sale',true);
//
//        //模糊搜索
//        if($search = $request->input('search'.'')){
//            $like = '%'.$search.'%';
//            $builder->where(function ($query) use ($like){
//                $query->where('title','like',$like)
//                    ->orWhere('description','like',$like)
//                    ->orWhereHas('skus',function ($query) use ($like){
//                        $query->where('title','like',$like)
//                            ->orWhere('description','like',$like);
//                    });
//            });
//        }
//
//        if($request->input('category_id') && $category = Category::find($request->input('category_id'))){
//            if($category->is_directory){
//                $builder->whereHas('category',function ($query)use($category){
//                   $query->where('path','like',$category->path.$category->id.'%');
//                });
//            }else{
//                $builder->where('category_id',$category->id);
//            }
//        }
//
//        //排序搜索
//        if($order = $request->input('order','')){
//            if(preg_match('/^(.+)_(asc|desc)$/',$order,$m)){
//                if(in_array($m[1],['price','sold_count','ration'])){
//                    $builder->orderBy($m[1],$m[2]);
//                }
//            }
//        }
//        $products = $builder->paginate(16);
//        return view('products.index',['products'=>$products,
//            'category'=>$category??null,
//            'categoryTree' => $categoryService->getCategoryTree(),
//            'filters'=>[
//                'search'=>$search,
//                'order'=>$order,
//
//            ]]);
        $page = $request->input('page',1);
        $perPage = 16;
        // 构建查询
        $params = [
            'index' => 'products',
            'type'  => '_doc',
            'body'  => [
                'from'  => ($page - 1) * $perPage, // 通过当前页数与每页数量计算偏移值
                'size'  => $perPage,
                'query' => [
                    'bool' => [
                        'filter' => [
                            ['term' => ['on_sale' => true]],
                        ],
                    ],
                ],
            ],
        ];
        // 是否有提交 order 参数，如果有就赋值给 $order 变量
        // order 参数用来控制商品的排序规则
        if($order = $request->input('order','')){
            // 是否是以 _asc 或者 _desc 结尾
            if(preg_match('/^(.+)_(asc|desc)$/',$order,$m)){
                if(in_array($m[1],['price','sold_count','rating'])){
                    $params['body']['sort'] = [$m[1]=>$m[2]];
                }
            }
        }

        if($request->input('category_id') && $category = Category::find($request->input('category_id'))){
            if ($category->is_directory) {
                // 如果是一个父类目，则使用 category_path 来筛选
                $params['body']['query']['bool']['filter'][] = [
                    'prefix' => ['category_path' => $category->path.$category->id.'-'],
                ];
            }else{
                // 否则直接通过 category_id 筛选
                $params['body']['query']['bool']['filter'][] = ['term' => ['category_id' => $category->id]];
            }
        }

        if($search=$request->input('search','')){
            // 将搜索词根据空格拆分成数组，并过滤掉空项
            $keywords = array_filter(explode(' ', $search));
            $params['body']['query']['bool']['must'] = [];
            // 遍历搜索词数组，分别添加到 must 查询中
            foreach ($keywords as $keyword) {
                $params['body']['query']['bool']['must'][] = [
                    'multi_match' => [
                        'query'  => $keyword,
                        'fields' => [
                            'title^2',
                            'long_title^2',
                            'category^2',
                            'description',
                            'skus.title^2',
                            'skus.description',
                            'properties.value',
                        ],
                    ],
                ];
            }

        }
        $result = app('es')->search($params);
        // 通过 collect 函数将返回结果转为集合，并通过集合的 pluck 方法取到返回的商品 ID 数组
        $producteIds = collect($result['hits']['hits'])->pluck('_id')->all();
        $productes = Product::query()
            ->whereIn('id',$producteIds)//Mysql 的 in 查询并不会按照参数的顺序把结果返回给我们
                //所以要用到mysql的FIND_IN_SET函数
            ->orderByRaw(sprintf("FIND_IN_SET(id,'%s')",join(',',$producteIds)))
            ->get();
        // 返回一个 LengthAwarePaginator 对象
        $perger = new LengthAwarePaginator($productes,$result['hits']['total'],$perPage,$page,[
            'path'=>route('products.index',false)// 手动构建分页的 url
        ]);

        return view('products.index',[
            'products'=>$perger,
            'filters'=>[
                'search'=>$search,
                'order'=>$order
            ],
            'category'=>$category??null
        ]);
    }

    public function show(Product $product, Request $request)
    {
        // 判断商品是否已经上架，如果没有上架则抛出异常。
        if (!$product->on_sale) {
            throw new InvalidRequestException('商品未上架');
        }

        $favored = false;
        if($user = $request->user()){
            // 从当前用户已收藏的商品中搜索 id 为当前商品 id 的商品
            // boolval() 函数用于把值转为布尔值
            $favored = boolval($user->favoriteProducts()->find($product->id));
        }

        $reviews = OrderItem::query()
            ->with(['order.user', 'productSku']) // 预先加载关联关系
            ->where('product_id', $product->id)
            ->whereNotNull('reviewed_at') // 筛选出已评价的
            ->orderBy('reviewed_at', 'desc') // 按评价时间倒序
            ->limit(10) // 取出 10 条
            ->get();

        return view('products.show', ['product' => $product,'favored'=>$favored,'reviews'=>$reviews]);
    }

    public function favor(Product $product, Request $request)
    {
        $user = $request->user();
        if ($user->favoriteProducts()->find($product->id)) {
            return [];
        }

        $user->favoriteProducts()->attach($product);

        return [];
    }

    public function disfavor(Product $product, Request $request)
    {
        $user = $request->user();
        $user->favoriteProducts()->detach($product);

        return [];
    }

    public function favorites(Request $request){
        $products = $request->user()->favoriteProducts()->paginate(16);
        return view('products.favorites',['products'=>$products]);
    }
}
