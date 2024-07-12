<?php

namespace app\commands;

use app\models\Product;
use app\models\ReviewBuyer;
use app\models\ReviewProduct;
use app\models\User;
use yii\console\Controller;
use yii\console\ExitCode;

class CalculateRatingCommand extends Controller
{
    public function actionIndex()
    {
        // сейчас рейтинг считается в момент создания отзыва, пока не используем
        return ExitCode::OK;

        $buyers = User::find()
            ->where(['role' => User::ROLE_BUYER])
            ->all();
        foreach ($buyers as $buyer) {
            $buyerId = $buyer->id;
            // todo #newarch feedback
            $reviews = ReviewBuyer::find()
                ->where(['buyer_id' => $buyerId])
                ->all();

            $totalRating = 0;
            $reviewCount = count($reviews);

            foreach ($reviews as $review) {
                $totalRating += $review->rating;
            }

            $averageRating = round(
                $reviewCount > 0 ? $totalRating / $reviewCount : 0,
                1
            );

            $buyer->rating = $averageRating;
            $buyer->save(false);
        }

        $products = Product::find()->all();

        foreach ($products as $product) {
            $productId = $product->id;
            // todo #newarch feedback
            $reviews = ReviewProduct::find()
                ->where(['product_id' => $productId])
                ->all();

            $totalRating = 0;
            $reviewCount = count($reviews);

            foreach ($reviews as $review) {
                $totalRating += $review->rating;
            }

            $averageRating = round(
                $reviewCount > 0 ? $totalRating / $reviewCount : 0,
                1
            );

            $product->rating = $averageRating;
            $product->save();
        }

        echo "Rating calculation completed.\n";

        return ExitCode::OK;
    }
}
