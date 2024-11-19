<?php

namespace app\services;

use app\components\responseFunction\Result;
use app\components\responseFunction\ResultAnswer;
use app\models\FeedbackBuyer;
use app\models\FeedbackProduct;
use app\models\Product;
use app\models\User;
use Throwable;
use Yii;

class RatingService
{
    public static function updateBuyerRating(int $buyerId): ResultAnswer
    {
        try {
            $buyer = User::findOne(['id' => $buyerId]);

            if (!$buyer) {
                return Result::notFound();
            }

            $feedbackCollection = FeedbackBuyer::find()
                ->select(['rating'])
                ->where(['buyer_id' => $buyer->id])
                ->column();
            $feedbackCollection[] = Yii::$app->params['baseRating'];

            $buyer->rating = round(
                array_sum($feedbackCollection) / count($feedbackCollection),
                2,
            );
            $buyer->feedback_count = count($feedbackCollection) - 1;

            if (!$buyer->save(false, ['rating', 'feedback_count'])) {
                return Result::errors($buyer->getFirstErrors());
            }

            return Result::success();
        } catch (Throwable $e) {
            return Result::errors(['base' => $e->getMessage()]);
        }
    }

    public static function updateProductRating(int $productId): ResultAnswer
    {
        try {
            $product = Product::findOne(['id' => $productId]);

            if (!$product) {
                return Result::notFound();
            }

            $feedbackCollection = FeedbackProduct::find()
                ->select(['rating'])
                ->where(['product_id' => $product->id])
                ->column();

            $product->rating = round(
                array_sum($feedbackCollection) / count($feedbackCollection),
                2,
            );
            $product->feedback_count = count($feedbackCollection);

            if (!$product->save(false, ['rating', 'feedback_count'])) {
                return Result::errors($product->getFirstErrors());
            }

            return Result::success();
        } catch (Throwable $e) {
            return Result::errors(['base' => $e->getMessage()]);
        }
    }
}
