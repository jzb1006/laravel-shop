<?php

namespace App\Admin\Controllers;

use App\Models\CouponCode;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use function foo\func;

class CouponCodesController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'App\Models\CouponCode';

    public function index(Content $content)
    {
        return $content->header('优惠券列表')
            ->body($this->grid());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new CouponCode);
        $grid->column('id', __('Id'))->sortable();
        $grid->column('name', __('名称'));
        $grid->column('code', __('优惠码'));
        $grid->description('描述');
        $grid->column('enabled', __('是否启用'))->display(function ($value){
            return $value ?'是':'否';
        });
        $grid->column('usage','用量')->display(function (){
           return "{$this->used} / {$this->total}";
        });
        $grid->column('created_at', __('创建时间'));
        $grid->actions(function ($actions){
           $actions->disableView();
        });
        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(CouponCode::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('code', __('Code'));
        $show->field('type', __('Type'));
        $show->field('value', __('Value'));
        $show->field('total', __('Total'));
        $show->field('used', __('Used'));
        $show->field('min_amount', __('Min amount'));
        $show->field('not_before', __('Not before'));
        $show->field('not_after', __('Not after'));
        $show->field('enabled', __('Enabled'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    public function edit($id, Content $content)
    {
        return $content->header('编辑优惠券')->body($this->form()->edit($id));
    }

    public function create(Content $content)
    {
        return $content->header('新增优惠券')->body($this->form());
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new CouponCode);
        $form->display('id','ID');
        $form->text('name', __('名称'))->rules('required');
        $form->text('code', __('优惠码'))->rules(function () use ($form){
            if($id = $form->model()->id){
                return 'nullable|unique:coupon_codes,code,'.$id.',id';
            }else{
                return 'nullable|unique:coupon_codes';
            }
        });
        $form->radio('type', __('类型'))->options(CouponCode::$typeMap)->rules('required');
        $form->decimal('value', __('折扣'))->rules(function ($form){
            if($form->type===CouponCode::TYPE_PERCENT){
                return 'required|numeric|between:1,99';
            }else{
                return 'required|numeric|min:0.01';
            }
        });
        $form->number('total', __('总量'));
        $form->decimal('min_amount', __('最低金额'));
        $form->datetime('not_before', __('开始时间'))->default(date('Y-m-d H:i:s'));
        $form->datetime('not_after', __('结束时间'))->default(date('Y-m-d H:i:s'));
        $form->radio('enabled','启用')->options(['1'=>'是','0'=>'否']);
        $form->saving(function (Form $form){
            if(!$form->code){
                $form->code = CouponCode::findAvailableCode();
            }
        });
        return $form;
    }
}
