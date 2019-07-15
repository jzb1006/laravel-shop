<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddCartRequest;
use App\Models\CartItem;
use App\Models\ProductSku;
use Illuminate\Http\Request;

class CartController extends Controller
{
    //

    public function index(Request $request){
           $cartItems = $request->user()->cartItems()->with([
               'productSku.product'
           ])->get();

           $addreddes = $request->user()->addresses()->orderBy('last_used_at','desc')->get();

           return view('cart.index',['cartItems'=>$cartItems,'addresses'=>$addreddes]);

    }

    public function add(AddCartRequest $request){
        $user = $request->user();
        $skuId = $request->input('sku_id');
        $amount = $request->input('amount');

        if($cart = $user->cartItems()->where('product_sku_id',$skuId)->first()){
            $cart->update([
                'amount'=>$cart->amount+$amount
            ]);
        }else{
            $cart = new CartItem(['amount'=>$amount]);
            $cart->user()->associate($user);
            $cart->productSku()->associate($skuId);
            $cart->save();
        }
        return [];
    }


    public function remove(Request $request,ProductSku $productSku){
        $request->user()->cartItems()->where('product_sku_id',$productSku->id)->delete();
        return [];
    }
}
