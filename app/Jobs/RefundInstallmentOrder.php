<?php

namespace App\Jobs;

use App\Exceptions\InvalidRequestException;
use App\Models\Installment;
use App\Models\InstallmentItem;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RefundInstallmentOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // 如果商品订单支付方式不是分期付款、订单未支付、订单退款状态不是退款中
        if(
            $this->order->payment_method !== 'installment'
            || !$this->order->paid_at
            || $this->order->refund_status !== Order::REFUND_STATUS_PROCESSING){

            return ;
        }
        // 找不到对应的分期付款
        if(!$installment = Installment::query()->where('order_id',$this->order->id)->first()){
            return ;
        }
        //便利对应分期付款的还款计划
        foreach ($installment->items as $item){
            //如果是退款成功和正在退款的就跳过
            if(!$item->paid_at || in_array($item->refund_status,[
                    InstallmentItem::REFUND_STATUS_SUCCESS,
                    InstallmentItem::REFUND_STATUS_PROCESSING
                ])){
                continue;
            }

            try{
                $this->refundInstallmentItem($item);
            }catch (\Exception $e){
                \Log::warning('分期付款退款失败'.$e->getMessage(),[
                    'installment_item_id'=>$item->id
                ]);
                //还款失败也跳过
                continue;
            }

            $installment->refreshRefundStatus();

        }
    }

    protected function refundInstallmentItem(InstallmentItem $item){
        $refundNo = $this->order->refund_no.'_'.$item->sequence;
        switch ($item->payment_method){
            case 'alipay':
                $ret = app('alipay')->refund([
                    'trade_no'=>$item->payment_no,// 使用支付宝交易号来退款
                    'refund_amount'=>$item->base,// 退款金额，单位元，只退回本金
                    'out_request_no'=>$refundNo     // 退款订单号
                ]);
                // 根据支付宝的文档，如果返回值里有 sub_code 字段说明退款失败
                if($ret->sub_code){
                    $item->update([
                        'refund_status'=>InstallmentItem::REFUND_STATUS_FAILED
                    ]);
                }else{
                    $item->update([
                        'refund_status'=>InstallmentItem::REFUND_STATUS_SUCCESS
                    ]);
                }
                break;
            default:
                throw new InvalidRequestException('未知订单支付方式'.$item->payment_method);
                break;
        }
    }
}
