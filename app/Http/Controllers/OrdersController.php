<?php

namespace App\Http\Controllers;

use App\Events\OrderReviewd;
use App\Exceptions\CouponCodeUnavailableException;
use App\Exceptions\InternalException;
use App\Exceptions\InvalidRequestException;
use App\Http\Requests\ApplyRefundRequest;
use App\Http\Requests\CrowdFundingOrderRequest;
use App\Http\Requests\OrderRequest;
use App\Http\Requests\SendReviewRequest;
use App\Jobs\CloseOrder;
use App\Models\CouponCode;
use App\Models\Order;
use App\Models\ProductSku;
use App\Models\UserAddress;
use App\Services\CartService;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\CssSelector\Exception\InternalErrorException;

class OrdersController extends Controller
{
    //
    public function index(Request $request){
        $orders = Order::query()
            ->with(['items.product','items.productSku'])
            ->where('user_id',$request->user()->id)
            ->orderBy('created_at','desc')
            ->paginate();
        return view('orders.index',['orders'=>$orders]);
    }

    public function store(OrderRequest $request,OrderService $orderService){
        $user = $request->user();
        $address = UserAddress::find($request->input('address_id'));
        $coupon = null;

        if($code = $request->input('coupon_code')){
            $coupon = CouponCode::where('code',$code)->first();
            if(!$coupon){
                throw new CouponCodeUnavailableException('优惠卷不存在');
            }
        }
        return $orderService->store($user,$address,$request->input('remark'),$request->input('items'),$coupon);
    }

    public function show(Order $order,Request $request){
        $this->authorize('own',$order);
        return view('orders.show',['order'=>$order->load(['items.productSku','items.product'])]);
    }

    public function received(Order $order,Request $request){
        //校验权限
        $this->authorize('own',$order);

        if($order->ship_status !== Order::SHIP_STATUS_DELIVERED){
            throw new InvalidRequestException('发货状态不正确');
        }
        //更新发货状态
        $order->update(['ship_status'=>Order::SHIP_STATUS_RECEIVED]);
        //返回原页面
        return $order;
    }

    public function review(Order $order)
    {
        // 校验权限
        $this->authorize('own', $order);
        // 判断是否已经支付
        if (!$order->paid_at) {
            throw new InvalidRequestException('该订单未支付，不可评价');
        }
        // 使用 load 方法加载关联数据，避免 N + 1 性能问题
        return view('orders.review', ['order' => $order->load(['items.productSku', 'items.product'])]);
    }

    public function sendReview(SendReviewRequest $request,Order $order)
    {
        // 校验权限
        $this->authorize('own', $order);
        if (!$order->paid_at) {
            throw new InvalidRequestException('该订单未支付，不可评价');
        }
        // 判断是否已经评价
        if ($order->reviewed) {
            throw new InvalidRequestException('该订单已评价，不可重复提交');
        }
        $reviews = $request->input('reviews');
        // 开启事务
        \DB::transaction(function () use ($reviews, $order) {
            // 遍历用户提交的数据
            foreach ($reviews as $review) {
                $orderItem = $order->items()->find($review['id']);
                // 保存评分和评价
                $orderItem->update([
                    'rating'      => $review['rating'],
                    'review'      => $review['review'],
                    'reviewed_at' => Carbon::now(),
                ]);
            }
            // 将订单标记为已评价
            event(new OrderReviewd($order));
            $order->update(['reviewed' => true]);
        });

        return redirect()->back();
    }

    public function applyRefund(Order $order,ApplyRefundRequest $request){
        $this->authorize('own',$order);

        if(!$order->paid_at){
            throw new InvalidRequestException('该订单未支付吗，不可退款');
        }

        if($order->refund_status !== Order::REFUND_STATUS_PENDING){
            throw new InvalidRequestException('该订单已经申请退款，请勿重复申请');
        }

        if($order->type===Order::TYPE_CROWDFUNDING){
            throw new InternalErrorException('众筹订单不支持对退款');
        }
        // 将用户输入的退款理由放到订单的 extra 字段中
        $extra = $order->extra ?:[];
        $extra['refund_reason'] = $request->input('reason');

        // 将订单退款状态改为已申请退款
        $order->update([
            'refund_status'=>Order::REFUND_STATUS_APPLIED,
            'extra'=>$extra
        ]);

        return $order;

    }

    public function crowdfunding(CrowdFundingOrderRequest $request,OrderService $orderService){
        $user = $request->user();
        $sku = ProductSku::find($request->input('sku_id'));
        $address = UserAddress::find($request->input('address_id'));
        $amount = $request->input('amount');
        return $orderService->crowdfunding($user,$address,$sku,$amount);
    }

}
