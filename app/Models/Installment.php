<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Installment extends Model
{
    //
    const STATUS_PENDING = 'pending';
    const STATUS_REPAYING = 'repaying';
    const STATUS_FINISHED = 'finished';

    public static $statusMap=[
        self::STATUS_PENDING =>'未执行',
        self::STATUS_REPAYING =>'还款中',
        self::STATUS_FINISHED =>'已完成'
    ];

    protected $fillable = ['no', 'total_amount', 'count', 'fee_rate', 'fine_rate', 'status'];

    protected static function boot(){
        parent::boot();

        //监听模型的创建事件，在写入之前触发
        static::creating(function ($model){
            //如果模型的no字段为空
            if(!$model->no){
               //调用 findAvailableNo 生成流水号
                $model->no = static::findAvailableNo();
                // 如果生成失败，则终止创建订单
                if(!$model->no){
                    return false;
                }
           }
        });
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
    public function order(){
        return $this->belongsTo(Order::class);
    }
    public function items(){
        return $this->hasMany(InstallmentItem::class);
    }

    public static function findAvailableNo(){
        $perfic = date('YmdHis');
        for ($i=0;$i<10;$i++){
            // 随机生成 6 位的数字
            $no = $perfic.str_pad(random_int(0,999999),6,'0',STR_PAD_LEFT);
            if(!static::query()->where('no',$no)->exists()){
                return $no;
            }
        }
        \Log::waring(sprintf('find installment no failed'));
        return false;
    }

    public function refreshRefundStatus(){
        $allSuccess = true;
        foreach ($this->items as $item){
            // 如果该还款计划已经还款，但退款状态不是成功
            if($item->paid_at && $item->refund_status !== InstallmentItem::REFUND_STATUS_SUCCESS){
                $allSuccess = false;
                break;
            }
        }

        if ($allSuccess){
            $this->order->update([
                'refund_status'=>Order::REFUND_STATUS_SUCCESS
            ]);
        }
    }
}
