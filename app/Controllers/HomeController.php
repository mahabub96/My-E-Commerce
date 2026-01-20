<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;
use App\Models\Category;

class HomeController extends Controller
{
	public function index()
	{
		$productModel = new Product();
		$categoryModel = new Category();

		$data = [
			'featured' => $productModel->getFeatured(8),
			'categories' => $categoryModel->getActive(),
		];

		// Render customer landing page without layout wrapping
		return $this->view('customer.index', $data, false);
	}
}
