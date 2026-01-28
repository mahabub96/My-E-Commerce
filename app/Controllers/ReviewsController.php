<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Middleware;
use App\Helpers\Validator;
use App\Models\Review;
use App\Models\Order;
use App\Services\ValidationRules;

class ReviewsController extends Controller
{
    private function getFinalStatuses(): array
    {
        // Accept both 'paid' and 'completed' as final statuses for review eligibility
        return ['paid', 'completed'];
    }

    public function index($productId)
    {
        $productId = (int)$productId;
        $reviewModel = new Review();
        $reviews = $reviewModel->byProduct($productId, 200);
        $avgRow = $reviewModel->averageForProduct($productId);

        $userId = Middleware::userId();
        $eligible = false;
        $userReview = null;
        $candidateOrderId = null;

        if ($userId) {
            $allowed = $this->getFinalStatuses();
            $orderModel = new Order();
            $hasCompleted = $orderModel->userHasCompletedOrderWithProduct((int)$userId, $productId, $allowed);
            $hasReviewed = $reviewModel->userHasReviewed((int)$userId, $productId);
            $eligible = $hasCompleted && !$hasReviewed;

            if ($eligible) {
                $orderModel = new Order();
                $candidateOrderId = $orderModel->findCandidateOrderForReview((int)$userId, $productId, $allowed);
            }

            if (!$eligible) {
                $userReview = $reviewModel->getUserReview((int)$userId, $productId);
            }
        }

        return $this->json([
            'success' => true,
            'reviews' => $reviews,
            'avg_rating' => isset($avgRow['avg']) ? (float)($avgRow['avg']) : 0.0,
            'review_count' => (int)($avgRow['cnt'] ?? 0),
            'can_review' => $eligible,
            'candidate_order_id' => $candidateOrderId,
            'user_review' => $userReview,
        ]);
    }

    public function store()
    {
        if (!Middleware::ensureCustomer()) {
            if ($this->request->isAjax()) {
                return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            Middleware::authorizeCustomer('/login');
            return;
        }

        $userId = (int)Middleware::userId();
        $input = $this->request->all();

        $validator = Validator::make($input, ValidationRules::review());

        if ($validator->fails()) {
            return $this->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $productId = (int)$input['product_id'];
        $orderId = isset($input['order_id']) ? (int)$input['order_id'] : 0;
        $rating = (float)$input['rating'];
        $comment = trim((string)($input['comment'] ?? ''));

        if (!is_numeric($input['rating']) || $rating < 1.0 || $rating > 5.0) {
            return $this->json(['success' => false, 'message' => 'Rating must be between 1.0 and 5.0'], 422);
        }

        $allowed = $this->getFinalStatuses();

        if ($orderId <= 0) {
            $orderModel = new Order();
            $orderId = $orderModel->findCandidateOrderForReview($userId, $productId, $allowed);
            if (!$orderId) {
                return $this->json(['success' => false, 'message' => 'No eligible completed order found for this product'], 400);
            }
        }

        $orderModel = new Order();
        if (!$orderModel->verifyOrderEligibility($orderId, $userId, $allowed)) {
            return $this->json(['success' => false, 'message' => 'Order not found or not eligible for reviews'], 400);
        }

        if (!$orderModel->orderContainsProduct($orderId, $productId)) {
            return $this->json(['success' => false, 'message' => 'Order does not contain this product'], 400);
        }

        $reviewModel = new Review();
        if ($reviewModel->userHasReviewed($userId, $productId)) {
            return $this->json(['success' => false, 'message' => 'User has already reviewed this product'], 409);
        }

        try {
            $id = $reviewModel->createReview([
                'user_id' => $userId,
                'product_id' => $productId,
                'order_id' => $orderId,
                'rating' => $rating,
                'comment' => $comment,
            ]);

            $avgRow = $reviewModel->averageForProduct($productId);
            $reviews = $reviewModel->byProduct($productId, 200);

            return $this->json([
                'success' => true,
                'id' => $id,
                'avg_rating' => (float)($avgRow['avg'] ?? 0),
                'review_count' => (int)($avgRow['cnt'] ?? 0),
                'reviews' => $reviews,
                'can_review' => false, // User just reviewed, can't review again
                'user_review' => ['id' => $id], // Mark that user has reviewed
            ], 201);
        } catch (\PDOException $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'ux_user_product') !== false || strpos($msg, 'Duplicate entry') !== false) {
                return $this->json(['success' => false, 'message' => 'User has already reviewed this product'], 409);
            }
            error_log('Review insert failed: ' . $e->getMessage());
            return $this->json(['success' => false, 'message' => 'Failed to save review'], 500);
        }
    }
}
