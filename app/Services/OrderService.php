<?php

namespace App\Services;


use App\Exceptions\CouponCodeUnavailableException;
use App\Exceptions\InvalidRequestException;
use App\Jobs\CloseOrder;
use App\Models\Order;
use App\Models\ProductSku;
use App\Models\User;
use App\Models\UserAddress;
use Carbon\Carbon;

class OrderService
{
    public function store(User $user,UserAddress $address,$remark,$items,$coupon = null){
        // 如果传入了优惠券，则先检查是否可用
        if($coupon){
            // 但此时我们还没有计算出订单总金额，因此先不校验
            $coupon->checkAvailable($user);
        }


        $order = \DB::transaction(function () use ($user,$address,$remark,$items,$coupon){
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

                $item = $order->items()->make([
                    'amount'=>$data['amount'],
                    'price'=>$sku->price,
                ]);

                $item->product()->associate($sku->product_id);
                $item->productSku()->associate($sku);
                $item->save();
                $totalAmount+=$sku->price*$data['amount'];
                if($sku->decreaseStock($data['amount'])<=0){
                    throw new InvalidRequestException('商品库存不足');
                }
            }


            if($coupon){
                // 总金额已经计算出来了，检查是否符合优惠券规则
                $coupon->checkAvailable($user,$totalAmount);
                // 把订单金额修改为优惠后的金额
                $totalAmount = $coupon->getAdjustedPrice($totalAmount);
                $order->couponCode()->associate($coupon);
                if($coupon->changeUsed()<=0){
                    throw new CouponCodeUnavailableException('该优惠券已兑换完');
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
