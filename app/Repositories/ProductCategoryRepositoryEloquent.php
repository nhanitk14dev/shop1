<?php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\ProductCategoryRepository;
use App\Traits\UploadPhotoTrait;
use App\Validators\ProductValidator;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Eloquent\BaseRepository;
use App\Models\ProductCategory;
/**
 * Class ProductRepositoryEloquent
 *
 * @package namespace App\Repositories;
 */
class ProductCategoryRepositoryEloquent extends BaseRepository implements ProductCategoryRepository
{
    use UploadPhotoTrait;

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return ProductCategory::class;
    }

    /**
     *
     * Specify Validator class name
     *
     *
     * @return mixed
     */
    public function validator()
    {

       // return ProductValidator::class;
    }


    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

    public function getModel()
    {
        return $this->model;
    }

    //3 cai nay can phai co de lay parent_id
    public function getCategories($parent_id, $children = false, $is_display = -1)
    {
        $model = $this->model->select("*")
            ->where('parent_id', $parent_id);
        if ($children) {

            if ($is_display !== -1) {
                $model->with(["children" => function ($q) use ($is_display) {
                    $q->where('is_display', $is_display);
                }]);

                
            } else {
                $model->with(["children"]);
            }
        }
        
        if ($is_display !== -1) {
            $model->where('is_display', $is_display);
        }
        $model->withTranslation();

        return $model->orderBy('level', 'asc')
            ->orderBy('position', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }

    public function arrTreeCategories($parent_id)
    {
        $result = $this->getCategories($parent_id);
        foreach ($result as $rs) {
            $rs->trees = $this->arrTreeCategories($rs->id);
        }
        return $result;
    }

    public function outTreeCategorySortTable($tree)
    {
        $string = '';
        if (empty($tree) || !$tree->count()) {
            $string .= '';
        } else {
            $string .= '<ul class="list-unstyled sortable-categories">';
            foreach ($tree as $rs) {
                $linkEdit = route("admin.product_category.edit", $rs->id);
                $linkDelete = route("admin.product_category.destroy", $rs->id);

                $string .= "<li class='ui-sortable-placeholder list-group-item' data-id='{$rs->id}'>";
                $string .= "<div class='tree-name'>{$rs->name}</div>";
                $string .= "<div class='buttons-control'>";
                $string .= "<a class='btn btn-xs btn-info' href='{$linkEdit}'>" . trans("button.edit") . "</a>";
                $string .= " <button class='btn-delete-record btn btn-xs btn-danger' type='button' data-title='" . trans('admin_message.alert_delete', ['id' => $rs->name]) . "' data-url='{$linkDelete}'>" . trans("button.delete") . "</button>";
                $string .= "</div>";
                if (!empty($rs->trees) && $rs->trees->count()) {
                    $string .= $this->outTreeCategorySortTable($rs->trees);
                }
                $string .= "</li>";
            }
            $string .= "</ul>";
        }
        return $string;
    }

    public function outTreeCategoryRadioCheckbox($tree, $type = "radio", $list_id = [], $disable_id = [], $root = false)
    {
        $string = '';
        if ((empty($tree) || !$tree->count()) && !$root) {
            $string .= '';
        } else {
            $string .= '<ul class="list-unstyled">';
            if ($root) {
                $checked = '';
                if (in_array(0, $list_id)) {
                    $checked = 'checked';
                }
                $string .= '<li class="root-tree"><input type="radio" class="with-gap radio-col-red" level="-1" id="category-0" name="category_id" ' . $checked . ' value="0" />';
                $string .= '<label for="category-0"> ' . trans("admin_product_category.attr.root") . '</label></li>';
                $root = false;
            }
            foreach ($tree as $rs) {
                $checked = '';
                if (in_array($rs->id, $list_id)) {
                    $checked = 'checked';
                }

                $disabled = '';
                if (in_array($rs->id, $disable_id)) {
                    $disabled = 'disabled';
                }

                $string .= '<li>';

                if ($type === 'checkbox') {
                    $string .= '<input type="' . $type . '" class="chk-col-red" level="' . $rs->level . '" id="category-' . $rs->id . '" name="category_id[]" ' . $checked . ' ' . $disabled . ' value="' . $rs->id . '" />';//dùng cho tạo sản phẩm category_id[]
                } else {
                    $string .= '<input type="' . $type . '" class="with-gap radio-col-red" level="' . $rs->level . '" id="category-' . $rs->id . '" name="category_id" ' . $checked . ' ' . $disabled . ' value="' . $rs->id . '" />';
                }
                $string .= '<label for="category-' . $rs->id . '"> ' . $rs->name . '</label>';

                if (!empty($rs->trees) && $rs->trees->count()) {
                    $string .= $this->outTreeCategoryRadioCheckbox($rs->trees, $type, $list_id, $disable_id, $root);
                }
                $string .= '</li>';
            }
            $string .= '</ul>';
        }
        return $string;
    }

    public function store(array $input)
    {
       
        $input["parent_id"] = $input["category_id"];
        unset($input["category_id"]);

        $input['is_display'] = !empty($input['is_display']) ? 1 : 0;

        $level = 0;
        if (!empty($input["parent_id"])) {
            $parentCategory = $this->model->findOrFail($input["parent_id"]);
            $level = $parentCategory->level + 1;
        }
        $input["level"] = $level;

        //$this->uploadPhotos($input);

        $category = $this->model->create($input);

        if (!empty($input["metadata"])) {
            $category->metaCreateOrUpdate($input["metadata"]);
        }

        if (!empty($input["catalogue"])) {
            $category->storeCatalogues($input["catalogue"]);
        }

        return $category;
    }

    public function update(array $input, $id)
    {
        $category = $this->model->findOrFail($id);

        $input["parent_id"] = $input["category_id"];
        unset($input["category_id"]);
        $input['is_display'] = !empty($input['is_display']) ? 1 : 0;

        $level = 0;
        if (!empty($input["parent_id"])) {
            $parentCategory = $this->model->findOrFail($input["parent_id"]);
            $level = $parentCategory->level + 1;
        }
        $input["level"] = $level;

        $category->update($input);

        if (!empty($input["metadata"])) {
            $category->metaCreateOrUpdate($input["metadata"]);
        }

        return $category;
    }

    public function destroy($id)
    {
        $category = $this->model->findOrFail($id);

        $category->delete();

        return true;
    }

    public function getAllChildrenIds(&$array, $parent_id)
    {
        $a = $this->model->where("parent_id", $parent_id)->pluck("id")->toArray();
        foreach ($a as $key => $value) {
            $array[] = $value;
            $this->getAllChildrenIds($array, $value);
        }
    }

    public function getCategoryBySlug($slug, $with = ['children'], $level = -1)
    {
        $locale = \App::getLocale();
        return \Cache::remember("{$locale}_category_by_slug_{$slug}." . json_encode($with) . ".{$level}", CACHE_TIME, function () use ($slug, $with, $level) {
            $model = $this->model->whereTranslation('slug', $slug)
                ->with($with);
            if ($level !== -1) {
                $model->where('level', $level);
            }
            return $model->firstOrFail();
        });
    }

    public function galleries($category_id, $product_id)
    {
        $model = MediaObject::where('level', 1)
            ->whereHas('product.categories', function ($q) use ($category_id) {
                $q->where('product_categories.id', $category_id);
            });
        if (!empty($product_id)) {
            $model->where('object_id', $product_id);
        }
        return $model->with(['product', 'media.products'])
            ->groupBy('media_id')
            ->paginate(20);
    }

    public function sort($positions)
    {
        $arr = explode('&', $positions);
        if ($arr && count($arr)) {
            for ($i = 0; $i < count($arr); $i++) {
                $_arr = explode('=', $arr[$i]);
                $this->model->where('id', $_arr[0])->update(['position' => $_arr[1]]);
            }
        }
    }

}
