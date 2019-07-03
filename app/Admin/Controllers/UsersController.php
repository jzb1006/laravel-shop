<?php

namespace App\Admin\Controllers;

use App\Models\User;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
class UsersController extends Controller
{
    use ModelForm;
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'App\Models\User';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        // 根据回调函数，在页面上用表格的形式展示用户记录
        return Admin::grid(User::class, function (Grid $grid) {
            // 创建一个列名为 ID 的列，内容是用户的 id 字段，并且可以在前端页面点击排序
            $grid->id('ID')->sortable();

            $grid->name('用户名');
            $grid->email('邮箱');
            $grid->email_verified('已经验证邮箱')->display(function ($value){
                return $value?'是':'否';
            });

            $grid->created_at('注册时间');
            // 不在页面显示 `新建` 按钮，因为我们不需要在后台新建用户
            $grid->disableCreateButton();

            $grid->actions(function ($actions){
                // 不在每一行后面展示查看按钮
                $actions->disableView();
                // 不在每一行后面展示删除按钮
                $actions->disableDelete();
                // 不在每一行后面展示删除按钮
                $actions->disableDelete();

            });
            $grid->tools(function ($tools) {

                // 禁用批量删除按钮
                $tools->batch(function ($batch) {
                    $batch->disableDelete();
                });
            });

        });

    }


    public function index()
    {
        return Admin::content(function (Content $content) {
            // 页面标题
            $content->header('用户列表');
            $content->body($this->grid());
        });
    }
}
