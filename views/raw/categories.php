<?php

use yii\helpers\Html;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <title>Document</title>
</head>
<body>
    <div class="container mt-5">
        <!-- Вкладки -->
        <ul class="nav nav-tabs" id="categoryTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" id="parent-categories-tab" data-bs-toggle="tab" href="#parent-categories" role="tab" aria-controls="parent-categories" aria-selected="true">Есть подкатегории</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="other-categories-tab" data-bs-toggle="tab" href="#other-categories" role="tab" aria-controls="other-categories" aria-selected="false">Нет подкатегорий</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="tree-categories-tab" data-bs-toggle="tab" href="#tree-categories" role="tab" aria-controls="tree-categories" aria-selected="false">Категории деревом</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="all-categories-tab" data-bs-toggle="tab" href="#all-categories" role="tab" aria-controls="all-categories" aria-selected="false">Все категории</a>
            </li>
        </ul>

        <div class="tab-content" id="categoryTabsContent">
            <div class="tab-pane fade show active" id="parent-categories" role="tabpanel" aria-labelledby="parent-categories-tab">
                <?php foreach ($categories as $category) : ?>
                    <?php if (!empty($category['subcategories'])) : ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0 text-primary"><?= $category['ru_name'] ?> (<?= $category['id'] ?>)</h5>
                            </div>
                            <div class="card-body">
                                <div class="subcategories">
                                    <div class="row">
                                        <?php foreach ($category['subcategories'] as $subcategory) : ?>
                                            <div class="col-md-4">
                                                <div class="subcategory mb-2">
                                                    <p class="text-secondary mb-1"><?= $subcategory['ru_name'] ?> (<?= $subcategory['id'] ?>)</p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="tab-pane fade" id="other-categories" role="tabpanel" aria-labelledby="other-categories-tab">
                <?php foreach ($categories as $category) : ?>
                    <?php if (empty($category['subcategories'])) : ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0 text-secondary"><?= $category['ru_name'] ?> (<?= $category['id'] ?>)</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Нет подкатегорий</p>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="tab-pane fade" id="tree-categories" role="tabpanel" aria-labelledby="tree-categories-tab">
                <ul class="list-group">
                    <?php function renderCategories($categories) { ?>
                        <?php foreach ($categories as $category) : ?>
                            <li class="list-group-item">
                                <strong><?= $category['ru_name'] ?> (<?= $category['id'] ?>)</strong>
                                <?php if (!empty($category['subcategories'])) : ?>
                                    <ul>
                                        <?php renderCategories($category['subcategories']); ?>
                                    </ul>
                                <?php else : ?>
                                    <span class="text-muted">Нет подкатегорий</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    <?php } ?>
                    <?php renderCategories($categories); ?>
                </ul>
            </div>

            <div class="tab-pane fade" id="all-categories" role="tabpanel" aria-labelledby="all-categories-tab">
                <ul class="list-group">
                    <?php foreach ($categories as $category) : ?>
                        <li class="list-group-item">
                            <strong><?= $category['ru_name'] ?> (<?= $category['id'] ?>)</strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
