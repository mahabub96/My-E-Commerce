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

		$recommended = $productModel->getRecommendedHome(4);
		$mostSold = $productModel->getMostSold(4);
		$productModel->attachRatings($recommended);
		$productModel->attachRatings($mostSold);

		foreach ($recommended as &$p) {
			$p['effective_price'] = Product::effectivePrice($p);
			$p['has_discount'] = Product::hasDiscount($p);
			$p['short_description'] = Product::shortDescription($p['description'] ?? '');
			if (empty($p['image']) && !empty($p['primary_image'])) {
				$p['image'] = $p['primary_image'];
			}
			if (empty($p['image'])) {
				$primary = $productModel->getPrimaryImage((int)$p['id']);
				$imgUrl = Product::resolveImageUrl($primary);
				if ($imgUrl) {
					$p['image'] = $imgUrl;
				}
			} else {
				$p['image'] = Product::resolveImageUrl($p['image']) ?? asset('images/' . basename($p['image']));
			}
		}
		unset($p);

		foreach ($mostSold as &$p) {
			$p['effective_price'] = Product::effectivePrice($p);
			$p['has_discount'] = Product::hasDiscount($p);
			$p['short_description'] = Product::shortDescription($p['description'] ?? '');
			if (empty($p['image']) && !empty($p['primary_image'])) {
				$p['image'] = $p['primary_image'];
			}
			if (empty($p['image'])) {
				$primary = $productModel->getPrimaryImage((int)$p['id']);
				$imgUrl = Product::resolveImageUrl($primary);
				if ($imgUrl) {
					$p['image'] = $imgUrl;
				}
			} else {
				$p['image'] = Product::resolveImageUrl($p['image']) ?? asset('images/' . basename($p['image']));
			}
		}
		unset($p);

		$data = [
			'recommended' => $recommended,
			'most_sold' => $mostSold,
			'categories' => $categoryModel->getActive(),
		];

		foreach ($data['categories'] as &$cat) {
			$icon = $cat['icon_path'] ?? $cat['image'] ?? null;
			$cat['icon_url'] = Category::resolveIconUrl($icon);
		}
		unset($cat);

		// Render customer landing page without layout wrapping
		return $this->view('customer.index', $data, false);
	}

	public function leaderboard()
	{
		$productModel = new Product();
		$items = $productModel->getMostSoldAll();
		$productModel->attachRatings($items);

		foreach ($items as &$p) {
			$p['effective_price'] = Product::effectivePrice($p);
			$p['has_discount'] = Product::hasDiscount($p);
			$p['short_description'] = Product::shortDescription($p['description'] ?? '');
			if (empty($p['image']) && !empty($p['primary_image'])) {
				$p['image'] = $p['primary_image'];
			}
			if (empty($p['image'])) {
				$primary = $productModel->getPrimaryImage((int)$p['id']);
				$imgUrl = Product::resolveImageUrl($primary);
				if ($imgUrl) {
					$p['image'] = $imgUrl;
				}
			} else {
				$p['image'] = Product::resolveImageUrl($p['image']) ?? asset('images/' . basename($p['image']));
			}
		}
		unset($p);

		return $this->json(['success' => true, 'items' => $items]);
	}
}
