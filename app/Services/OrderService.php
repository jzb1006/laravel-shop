<?php

namespace App\Services;


use App\Exceptions\InvalidRequestException;
use App\Jobs\CloseOrder;
use App\Models\Order;
use App\Models\ProductSku;
use App\Models\User;
use App\Models\UserAddress;
use Carbon\Carbon;

class OrderService
{
    public function store(User $user,UserAddress $address,$remark,$items){
        $order = \DB::transaction(function () use ($user,$address,$remark,$items){
            //更新地址最后使用时间
            $address->update([
                'last_used_at'=>Carbon::now()
            ]);

            // 创建一个订单
            $order = new Order([
                'address'=>[
                    'address'=>$address->full_address,
                    'zip'=>$address->zip,
                    'contact_name'=>$address->contact_name,
                    'contact_phone'=>$address->contact_phone
                ],
                'remark'=>$remark,
                'total_amount'=>0,
            ]);
            //关联用户
            $order->user()->associate($user);
            $order->save();

            $totalAmount = 0;

            foreach ($items as $data){
                $sku = ProductSku::find($data['sku_id']);

                $items = $order->items()->make([
                    'amount'=>$data['amount'],
                    'price'=>$sku->price,
                ]);

                $items->product()->associate($sku->product_id);
                $items->productSku()->associate($sku);
                $items->save();
                $totalAmount+=$sku->price*$data['amount'];
                if($sku->decreaseStock($data['amount'])<=0){
                    throw new InvalidRequestException('商品库存不足');
                }
            }

            $order->update([
                'total_amount'=>$totalAmount
            ]);

            $skuIds = collect($items)->pluck('sku_id')->all();
            app(CartService::class)->remove($skuIds);

            return $order;
        });

        dispatch(new CloseOrder($order,config('app.order_ttl')));
        return $order;
    }

}