<?php

namespace App\Admin\Controllers;

use App\Models\Category;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Http\Request;

class CategoriesController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'App\Models\Category';

    public function index(Content $content)
    {
        return $content->header('商品类目')
            ->body($this->grid());
    }

    public function edit($id, Content $content)
    {
        return $content->header('编辑商品类目')
            ->body($this->form(true)->edit($id));
    }

    public function create(Content $content)
    {
        return $content->header('创建商品类目')
            ->body($this->form());
    }


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Category);

        $grid->column('id', __('Id'));
        $grid->column('name', __('名称'));
        $grid->column('parent_id', __('Parent id'));
        $grid->column('is_directory', __('是否目录'))->display(function ($value){
            return $value?'是':'否';
        });
        $grid->column('level', __('层级'));
        $grid->column('path', __('类目路径'));
        $grid->actions(function ($action){
            $action->disableView();
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
        $show = new Show(Category::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('parent_id', __('Parent id'));
        $show->field('is_directory', __('Is directory'));
        $show->field('level', __('Level'));
        $show->field('path', __('Path'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form($isEditing=false)
    {
        $form = new Form(new Category);

        $form->text('name', __('类目名称'))->rules('required');
        if($isEditing){
            $form->display('is_directory','是否目录')->with(function ($value){
               return $value?'是':'否';
            });
            $form->display('parent.name','父目录');
        }else{
            $form->radio('is_directory','是否目录')
                ->options(['1'=>'是','0'=>'否'])
                ->default('0')
                 ->rules('required');
            $form->select('parent_id','父类目')->ajax('/admin/api/categories');
        }
        $form->number('level', __('Level'));
        $form->text('path', __('Path'));

        return $form;
    }

    public function apiIndex(Request $request){
        $search = $request->input('q');
        $result = Category::query()
            ->where('is_directory',boolval($request->input('is_directory',true)))
            ->where('name','like','%'.$search.'%')
            ->paginate();

        // 把查询出来的结果重新组装成 Laravel-Admin 需要的格式
        $result->setCollection($result->getCollection()->map(function (Category $category){
            return ['id'=>$category->id,'text'=>$category->full_name];
        }));

        return $result;
    }
}
