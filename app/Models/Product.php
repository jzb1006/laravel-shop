<?php

namespace App\Models;

use App\Exceptions\InternalException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class Product extends Model
{
    const TYPE_NORMAL = 'normal';
    const TYPE_CROWDFUNDING = 'crowdfunding';

    public static $typeMap = [
        self::TYPE_NORMAL=>'普通商品',
        self::TYPE_CROWDFUNDING=>'众筹商品'
    ];
    //
    protected $fillable = [
        'title', 'long_title','description', 'image', 'on_sale',
        'rating', 'sold_count', 'review_count', 'price','type'
    ];

    protected $casts = [
        'on_sale' => 'boolean', // on_sale 是一个布尔类型的字段
    ];

    // 与商品SKU关联
    public function skus()
    {
        return $this->hasMany(ProductSku::class);
    }
    
    public function getImageUrlAttribute(){
        if(Str::startsWith($this->attributes['image'],['http://','https://'])){
            return $this->attributes['image'];
        }

        return config('filesystems.disks.admin.url').'/'.$this->attributes['image'];
    }

    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function crowdfunding(){
        return $this->hasOne(CrowdfundingProduct::class);
    }

    public function properties(){
        return $this->hasMany(ProductProperty::class);
    }

    public function getGroupedPropertiesAttribute(){
        return $this->properties
            // 按照属性名聚合，返回的集合的 key 是属性名，value 是包含该属性名的所有属性集合
            ->groupBy('name')
            ->map(function ($properties){
                return $properties->pluck('value')->all();
            });
    }

    public function toESArray(){
        //取出和ES对应的字段
        $arr = array_only($this->toArray(),[
            'id',
            'type',
            'title',
            'category_id',
            'long_title',
            'on_sale',
            'rating',
            'sold_count',
            'review_count',
            'price',
        ]);
        // 如果商品有类目，则 category 字段为类目名数组，否则为空字符串
        $arr['category'] = $this->category?explode('-',$this->category->full_name):'';
        // 类目的 path 字段
        $arr['categoty_path'] = $this->category?$this->category->path:'';
        // strip_tags 函数可以将 html 标签去除
        $arr['description'] = strip_tags($this->description);
        //取出SKU的字段
        $arr['skus']=$this->skus->map(function (ProductSku $sku){
            return array_only($sku->toArray(),['title','description','price']);
        });
        //取出商品属性的字段
        $arr['properties'] = $this->properties->map(function (ProductProperty $property){
            return array_only($property->toArray(),['name','value']);
        });
        return $arr;
    }


}
