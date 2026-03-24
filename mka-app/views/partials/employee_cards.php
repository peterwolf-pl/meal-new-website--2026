<?php if (!empty($employees)): ?>
    <section class="employee-section" aria-label="Pracownicy zespołu">
        <div class="employee-section-head">
            <p class="accordion-kicker">Zespół</p>
            <h3 class="employee-section-title">Pracownicy</h3>
        </div>

        <div class="employee-grid">
            <?php foreach ($employees as $employee): ?>
                <?php
                $photo = null;
                foreach ($employee['media'] ?? [] as $asset) {
                    if (($asset['kind'] ?? '') === 'image') {
                        $photo = $asset;
                        break;
                    }
                }
                $projects = preg_split('/\r\n|\r|\n/', trim((string) ($employee['employee_projects'] ?? ''))) ?: [];
                $projects = array_values(array_filter(array_map('trim', $projects)));
                ?>
                <article class="employee-card">
                    <?php if ($photo !== null): ?>
                        <div class="employee-photo">
                            <img src="<?= e($photo['public_url']) ?>" alt="<?= e(!empty($photo['is_decorative']) ? '' : ($photo['alt_text'] ?? ($employee['title'] ?? ''))) ?>">
                        </div>
                    <?php endif; ?>

                    <div class="employee-copy">
                        <header class="employee-head">
                            <h4 class="employee-name"><?= e($employee['title'] ?? '') ?></h4>
                            <?php if (!empty($employee['summary'])): ?>
                                <p class="employee-role"><?= e($employee['summary']) ?></p>
                            <?php endif; ?>
                        </header>

                        <?php if (!empty($employee['contact_email'])): ?>
                            <p class="employee-contact">
                                <a href="mailto:<?= e((string) $employee['contact_email']) ?>"><?= e((string) $employee['contact_email']) ?></a>
                            </p>
                        <?php endif; ?>

                        <div class="employee-bio richtext accordion-richtext">
                            <?= trim((string) ($employee['body'] ?? '')) !== '' ? (string) $employee['body'] : '<p>Bio w przygotowaniu.</p>' ?>
                        </div>

                        <?php if ($projects !== []): ?>
                            <div class="employee-projects">
                                <p class="accordion-kicker">Projekty</p>
                                <ul>
                                    <?php foreach ($projects as $project): ?>
                                        <li><?= e($project) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
