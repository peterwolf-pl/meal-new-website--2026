<?php if (!empty($breadcrumbs)): ?>
    <nav class="breadcrumbs" aria-label="Breadcrumb">
        <ol>
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <li>
                    <?php if ($index < count($breadcrumbs) - 1): ?>
                        <a href="<?= e($crumb['href']) ?>"><?= e($crumb['label']) ?></a>
                    <?php else: ?>
                        <span aria-current="page"><?= e($crumb['label']) ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>
<?php endif; ?>
