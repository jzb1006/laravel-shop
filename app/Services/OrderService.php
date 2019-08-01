<?php

namespace App\Services;


use App\Exceptions\CouponCodeUnavailableException;
use App\Exceptions\InvalidRequestException;
use App\Jobs\CloseOrder;
use App\Jobs\RefundInstallmentOrder;
use App\Models\Order;
use App\Models\ProductSku;
use App\Models\User;
use App\Models\UserAddress;
use Carbon\Carbon;
use Symfony\Component\CssSelector\Exception\InternalErrorException;

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
                    'contact_phone'=>$address->contact_phone,

                ],
                'remark'=>$remark,
                'total_amount'=>0,
                'type'=>Order::TYPE_NORMAL
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

    public function crowdfunding(User $user,UserAddress $address,ProductSku $sku,$amount){
        $order = \DB::transaction(function ()use($amount,$sku,$user,$address){
            //更新地址使用时间
            $address->update([
                'last_used_at'=>Carbon::now()
            ]);

            $order = new Order([
                'address'=>[
                    'address'=>$address->full_address,
                    'zip'=>$address->zip,
                    'contact_name'=>$address->contact_name,
                    'contact_phone'=>$address->contact_phone,

                ],
                'remark'=>'',
                'total_amount' =>$sku->price*$amount,
                'type'=>Order::TYPE_CROWDFUNDING
            ]);

            //订单关联到当前用户
            $order->user()->associate($user);
            $order->save();

            //创建一个新的订单项并与SKU关联
            $item = $order->items()->make([
                'amount'=>$amount,
                'price'=>$sku->price
            ]);

            $item->product()->associate($sku->product_id);
            $item->productSku()->associate($sku);
            $item->save();

            //较少SKU的库存
            if($sku->decreaseStock($amount)<=0){
                throw new InternalErrorException('该商品库存不足');
            }

            return $order;


        });

        // 众筹结束时间减去当前时间得到剩余秒数
        $crowdfundingTtl = $sku->product->crowdfunding->end_at->getTimestamp() - time();
        // 剩余秒数与默认订单关闭时间取较小值作为订单关闭时间
        dispatch(new CloseOrder($order, min(config('app.order_ttl'), $crowdfundingTtl)));

        return $order;
    }

    public function refundOrder(Order $order){
        switch ($order->payment_method){
            case "wechat":

                break;
            case "alipay":

                $refundNo =  Order::getAvailableRefundNo();
                $ret = app('alipay')->refund([
                    'out_trade_no'=>$order->no,// 之前的订单流水号
                    'refund_amount'=>$order->total_amount,// 退款金额，单位元
                    'out_request_no'=>$refundNo// 退款订单号
                ]);
                // 根据支付宝的文档，如果返回值里有 sub_code 字段说明退款失败
                if($ret->sub_code){
                    // 将退款失败的保存存入 extra 字段
                    $extra = $order->extra;
                    $extra['refund_failed_code'] = $ret->sub_code;
                    $order->update([
                        'refund_no'=>$refundNo,
                        'refund_status'=>Order::REFUND_STATUS_FAILED,
                        'extra'=>$extra
                    ]);
                }else{
                    $order->update([
                        'refund_no'=>$refundNo,
                        'refund_status'=>Order::REFUND_STATUS_SUCCESS
                    ]);
                }
                break;
            case 'installment':
                $order->update([
                    'refund_no' => Order::getAvailableRefundNo(), // 生成退款订单号
                    'refund_status' => Order::REFUND_STATUS_PROCESSING, // 将退款状态改为退款中
                ]);
                // 触发退款异步任务
                dispatch(new RefundInstallmentOrder($order));
                break;
            default:
                throw new InternalErrorException('未知订单支付方式'.$order->payment_method);
                break;
        }
    }

}
