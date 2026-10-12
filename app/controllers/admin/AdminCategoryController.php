<?php
require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/app/models/CategoryModel.php';

class AdminCategoryController extends Controller {
    
    public function index(): void {
        $this->requireRole('admin');
        $categories = (new CategoryModel())->all();
        $this->render('admin/category/index', [
            'title' => 'Quản lý danh mục', 
            'categories' => $categories
        ]);
    }

    public function store(): void {
        $this->requireRole('admin');
        verifyCsrf();
        $name = trim($this->input('name'));
        
        if (!$name) { 
            $this->flash('error', 'Tên danh mục không được trống.'); 
            $this->redirect('/admin/categories'); 
        }

        (new CategoryModel())->insert([
            'name' => $name, 
            'slug' => slug($name)
        ]);
        
        $this->flash('success', 'Thêm danh mục thành công!');
        $this->redirect('/admin/categories');
    }

    // public function delete(string $id): void {
    //     $this->requireRole('admin');
    //     $intId = (int)$id;

    //     if ($intId <= 0) {
    //         $this->json(['success' => false, 'message' => 'ID không hợp lệ.'], 400);
    //     }
        
    //     try {
    //         verifyCsrf();
    //         $model = new CategoryModel();
    //         $model->delete($intId);
    //         $this->json(['success' => true]);
    //     } catch (Exception $e) {
    //         // Lỗi Foreign Key thường xảy ra khi danh mục còn hoa bên trong
    //         $this->json([
    //             'success' => false, 
    //             'message' => 'Không thể xóa vì vẫn còn sản phẩm thuộc danh mục này!'
    //         ], 400);
    //     }
    // }

    public function delete(string $id): void {
    $this->requireRole('admin');
    $intId = (int)$id;

    if ($intId <= 0) {
        $this->json(['success' => false, 'message' => 'ID không hợp lệ.'], 400);
        return; // Thêm return để dừng thực thi nếu ID lỗi
    }
    
    try {
        verifyCsrf();
        $model = new CategoryModel();
        $model->delete($intId);
        $this->json(['success' => true]);
    } catch (Exception $e) {
        // Sửa lại thông báo tại đây để làm rõ vấn đề "sản phẩm ẩn"
        $this->json([
            'success' => false, 
            'message' => 'Danh mục vẫn còn sản phẩm liên kết (bao gồm cả các sản phẩm đã ẩn). Hãy xóa vĩnh viễn sản phẩm hoặc đổi danh mục cho chúng trước khi thực hiện thao tác này.'
        ], 400);
    }
}
}   