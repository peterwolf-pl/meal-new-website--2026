<?php if (!empty($flashMessages)): ?>
    <div class="flash-stack" role="status" aria-live="polite">
        <?php foreach ($flashMessages as $message): ?>
            <div class="flash flash-<?= e($message['type']) ?>">
                <p><?= e($message['message']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
