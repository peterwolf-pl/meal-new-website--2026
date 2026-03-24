<section class="admin-page-head">
    <div>
        <p class="section-kicker">Treści</p>
        <h1><?= e($pageTitle) ?></h1>
    </div>
    <div class="admin-actions">
        <a class="button-secondary" href="/admin/content">Powrót do listy</a>
    </div>
</section>

<?php
$imageAssets = array_values(array_filter(
    $mediaAssets,
    static fn(array $asset): bool => ($asset['kind'] ?? '') === 'image' && !empty($asset['public_url'])
));
?>

<?php if (!empty($errors)): ?>
    <div class="form-error-box">
        <p>Popraw pola formularza zaznaczone poniżej.</p>
    </div>
<?php endif; ?>

<form method="post" action="/admin/content/save" class="admin-form-stack">
    <input type="hidden" name="_token" value="<?= e($app->csrf()->token()) ?>">
    <input type="hidden" name="id" value="<?= e((string) ($form['id'] ?? '')) ?>">

    <section class="admin-form-card">
        <h2>Typ i publikacja</h2>
        <div class="admin-form-grid columns-4">
            <label>
                <span>Typ</span>
                <select name="content_type" data-content-type-select>
                    <?php foreach ($contentTypes as $contentType): ?>
                        <option value="<?= e($contentType) ?>" <?= selected($contentType, $form['content_type']) ?>><?= e($app->navigation()->typeLabel($contentType)) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['content_type'])): ?><small class="form-error"><?= e($errors['content_type']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Sekcja</span>
                <select name="section_key" data-section-select>
                    <?php foreach ($sectionOptions as $section): ?>
                        <option value="<?= e($section) ?>" <?= selected($section, $form['section_key']) ?>><?= e($app->navigation()->sectionLabel($section)) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['section_key'])): ?><small class="form-error"><?= e($errors['section_key']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Status</span>
                <select name="status">
                    <?php foreach ($statusOptions as $status): ?>
                        <option value="<?= e($status) ?>" <?= selected($status, $form['status']) ?>><?= e($status) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['status'])): ?><small class="form-error"><?= e($errors['status']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Kolejność</span>
                <input type="number" name="sort_order" value="<?= e((string) $form['sort_order']) ?>">
            </label>
            <label>
                <span>Data publikacji</span>
                <input type="text" name="published_at" value="<?= e((string) $form['published_at']) ?>" placeholder="YYYY-MM-DD HH:MM:SS">
            </label>
            <label data-type-visibility="EXHIBITION,EVENT,PROJECT">
                <span>Start wydarzenia</span>
                <input type="text" name="event_start" value="<?= e((string) $form['event_start']) ?>" placeholder="YYYY-MM-DD HH:MM:SS">
                <?php if (!empty($errors['event_start'])): ?><small class="form-error"><?= e($errors['event_start']) ?></small><?php endif; ?>
            </label>
            <label data-type-visibility="EXHIBITION,EVENT,PROJECT">
                <span>Koniec wydarzenia</span>
                <input type="text" name="event_end" value="<?= e((string) $form['event_end']) ?>" placeholder="YYYY-MM-DD HH:MM:SS">
            </label>
            <label data-type-visibility="EXHIBITION,EVENT,PROJECT">
                <span>Lokalizacja</span>
                <input type="text" name="event_location" value="<?= e((string) $form['event_location']) ?>">
            </label>
            <label data-type-visibility="EXHIBITION,EVENT,PROJECT">
                <span>URL rejestracji</span>
                <input type="url" name="registration_url" value="<?= e((string) $form['registration_url']) ?>">
            </label>
            <label data-type-visibility="COLLECTION">
                <span>Grupa kolekcji</span>
                <select name="collection_group">
                    <option value="">-</option>
                    <option value="MKA" <?= selected('MKA', $form['collection_group']) ?>>MKA</option>
                    <option value="CDA" <?= selected('CDA', $form['collection_group']) ?>>CDA</option>
                </select>
            </label>
            <label data-type-visibility="COLLECTION,GALLERY">
                <span>Autor / twórca</span>
                <input type="text" name="creator_name" value="<?= e((string) $form['creator_name']) ?>">
                <?php if (!empty($errors['creator_name'])): ?><small class="form-error"><?= e($errors['creator_name']) ?></small><?php endif; ?>
            </label>
            <label data-type-visibility="COLLECTION,GALLERY">
                <span>Rok</span>
                <input type="text" name="item_year" value="<?= e((string) $form['item_year']) ?>">
            </label>
        </div>
        <label class="checkbox-field">
            <input type="checkbox" name="featured" value="1" <?= checked(!empty($form['featured'])) ?>>
            <span>Pokaż na stronie głównej</span>
        </label>
    </section>

    <section class="admin-form-card" data-type-panel="EMPLOYEE" hidden>
        <h2>Dane pracownika</h2>
        <div class="admin-form-grid columns-2">
            <label>
                <span>Adres kontaktowy</span>
                <input type="email" name="contact_email" value="<?= e((string) ($form['contact_email'] ?? '')) ?>" placeholder="imie.nazwisko@mkal.pl">
                <?php if (!empty($errors['contact_email'])): ?><small class="form-error"><?= e($errors['contact_email']) ?></small><?php endif; ?>
            </label>
            <div class="admin-inline-note">
                <strong>Układ pracownika</strong>
                <p>Imię i nazwisko, rola i bio wpisujesz niżej w sekcjach PL/EN. Zdjęcia wybierasz w sekcji mediów na końcu formularza.</p>
            </div>
        </div>
    </section>

    <section class="admin-form-card">
        <h2>Treść PL</h2>
        <div class="admin-form-grid columns-2">
            <label>
                <span>Slug</span>
                <input type="text" name="translations[pl][slug]" value="<?= e($form['translations']['pl']['slug']) ?>" required>
                <?php if (!empty($errors['translations.pl.slug'])): ?><small class="form-error"><?= e($errors['translations.pl.slug']) ?></small><?php endif; ?>
            </label>
            <label>
                <span data-type-label data-default-label="Tytuł" data-employee-label="Imię i nazwisko">Tytuł</span>
                <input type="text" name="translations[pl][title]" value="<?= e($form['translations']['pl']['title']) ?>" required>
                <?php if (!empty($errors['translations.pl.title'])): ?><small class="form-error"><?= e($errors['translations.pl.title']) ?></small><?php endif; ?>
            </label>
            <label class="span-2">
                <span data-type-label data-default-label="Summary" data-employee-label="Rola w muzeum / zakres obowiązków">Summary</span>
                <textarea
                    name="translations[pl][summary]"
                    rows="3"
                    data-seo-count
                    data-seo-label="Summary PL"
                    data-seo-ideal="120-220"
                    data-locale="pl"
                    data-role="summary"
                ><?= e($form['translations']['pl']['summary']) ?></textarea>
                <small class="helper-note seo-counter" data-seo-output></small>
                <?php if (!empty($errors['translations.pl.summary'])): ?><small class="form-error"><?= e($errors['translations.pl.summary']) ?></small><?php endif; ?>
            </label>
            <label class="span-2">
                <span data-type-label data-default-label="Body" data-employee-label="Bio">Body</span>
                <textarea
                    class="wysiwyg"
                    id="content-body-pl"
                    name="translations[pl][body]"
                    rows="12"
                    data-keyword-body="pl"
                ><?= e($form['translations']['pl']['body']) ?></textarea>
                <?php if (!empty($errors['translations.pl.body'])): ?><small class="form-error"><?= e($errors['translations.pl.body']) ?></small><?php endif; ?>
                <div class="content-insert-tools" data-editor-tools data-target-editor="content-body-pl">
                    <label>
                        <span>Wstaw obraz do Body PL</span>
                        <select data-image-select>
                            <option value="">- wybierz obraz z biblioteki -</option>
                            <?php foreach ($imageAssets as $asset): ?>
                                <option
                                    value="<?= e((string) $asset['public_url']) ?>"
                                    data-alt="<?= e((string) ($asset['alt_text'] ?? $asset['title'])) ?>"
                                    data-caption="<?= e((string) ($asset['caption'] ?? '')) ?>"
                                >
                                    #<?= e((string) $asset['id']) ?> <?= e((string) $asset['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <div class="admin-actions">
                        <button class="button-secondary" type="button" data-insert-image>Wstaw obraz</button>
                        <a class="button-ghost" href="/admin/media/new">Dodaj nowy obraz</a>
                    </div>
                </div>
            </label>
            <label class="span-2" data-type-panel="EMPLOYEE" hidden>
                <span>Projekty</span>
                <textarea name="translations[pl][employee_projects]" rows="4" placeholder="Lista projektów pracownika, po jednym w wierszu lub w formie krótkiego opisu."><?= e($form['translations']['pl']['employee_projects'] ?? '') ?></textarea>
                <?php if (!$supportsEmployeeProjects): ?>
                    <small class="helper-note">To pole zacznie zapisywać się do bazy po uruchomieniu najnowszej migracji w <code>/admin/database</code>.</small>
                <?php endif; ?>
            </label>
            <label class="span-2">
                <span>Keywords</span>
                <input type="text" name="translations[pl][seo_keywords]" value="<?= e($form['translations']['pl']['seo_keywords'] ?? '') ?>" placeholder="np. druk, typografia, książka artystyczna" data-keywords-input="pl">
                <small class="helper-note">Wpisuj słowa kluczowe po przecinku.</small>
                <div class="keyword-analysis" data-keyword-analysis="pl"></div>
            </label>
            <label>
                <span>SEO title</span>
                <input type="text" name="translations[pl][seo_title]" value="<?= e($form['translations']['pl']['seo_title']) ?>">
            </label>
            <label>
                <span>Meta description</span>
                <textarea
                    name="translations[pl][meta_description]"
                    rows="3"
                    data-seo-count
                    data-seo-label="Meta description PL"
                    data-seo-ideal="140-160"
                    data-locale="pl"
                    data-role="meta"
                    data-keyword-meta="pl"
                ><?= e($form['translations']['pl']['meta_description']) ?></textarea>
                <small class="helper-note seo-counter" data-seo-output></small>
            </label>
            <label>
                <span>OG title</span>
                <input
                    type="text"
                    name="translations[pl][og_title]"
                    value="<?= e($form['translations']['pl']['og_title']) ?>"
                    data-seo-count
                    data-seo-label="OG title PL"
                    data-seo-ideal="55-70"
                >
                <small class="helper-note seo-counter" data-seo-output></small>
            </label>
            <label>
                <span>OG description</span>
                <textarea
                    name="translations[pl][og_description]"
                    rows="3"
                    data-seo-count
                    data-seo-label="OG description PL"
                    data-seo-ideal="110-200"
                ><?= e($form['translations']['pl']['og_description']) ?></textarea>
                <small class="helper-note seo-counter" data-seo-output></small>
            </label>
        </div>
    </section>

    <section class="admin-form-card">
        <div class="admin-form-card-head">
            <button
                class="button-secondary"
                type="button"
                data-translate-content
                data-translate-url="/admin/content/translate"
                data-loading-label="Tłumaczę..."
            >
                Przetłumacz z PL
            </button>
            <h2>Treść EN</h2>
        </div>
        <p class="helper-note translation-status" data-translate-status hidden></p>
        <p class="helper-note">Przycisk tłumaczy pola z sekcji PL do EN. Body zachowuje formatowanie HTML z edytora.</p>
        <div class="admin-form-grid columns-2">
            <label>
                <span>Slug</span>
                <input type="text" name="translations[en][slug]" value="<?= e($form['translations']['en']['slug']) ?>">
                <?php if (!empty($errors['translations.en.slug'])): ?><small class="form-error"><?= e($errors['translations.en.slug']) ?></small><?php endif; ?>
            </label>
            <label>
                <span data-type-label data-default-label="Tytuł" data-employee-label="Imię i nazwisko">Tytuł</span>
                <input type="text" name="translations[en][title]" value="<?= e($form['translations']['en']['title']) ?>">
            </label>
            <label class="span-2">
                <span data-type-label data-default-label="Summary" data-employee-label="Rola w muzeum / zakres obowiązków">Summary</span>
                <textarea
                    name="translations[en][summary]"
                    rows="3"
                    data-seo-count
                    data-seo-label="Summary EN"
                    data-seo-ideal="120-220"
                    data-locale="en"
                    data-role="summary"
                ><?= e($form['translations']['en']['summary']) ?></textarea>
                <small class="helper-note seo-counter" data-seo-output></small>
            </label>
            <label class="span-2">
                <span data-type-label data-default-label="Body" data-employee-label="Bio">Body</span>
                <textarea
                    class="wysiwyg"
                    id="content-body-en"
                    name="translations[en][body]"
                    rows="12"
                    data-keyword-body="en"
                ><?= e($form['translations']['en']['body']) ?></textarea>
                <div class="content-insert-tools" data-editor-tools data-target-editor="content-body-en">
                    <label>
                        <span>Wstaw obraz do Body EN</span>
                        <select data-image-select>
                            <option value="">- wybierz obraz z biblioteki -</option>
                            <?php foreach ($imageAssets as $asset): ?>
                                <option
                                    value="<?= e((string) $asset['public_url']) ?>"
                                    data-alt="<?= e((string) ($asset['alt_text'] ?? $asset['title'])) ?>"
                                    data-caption="<?= e((string) ($asset['caption'] ?? '')) ?>"
                                >
                                    #<?= e((string) $asset['id']) ?> <?= e((string) $asset['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <div class="admin-actions">
                        <button class="button-secondary" type="button" data-insert-image>Wstaw obraz</button>
                        <a class="button-ghost" href="/admin/media/new">Dodaj nowy obraz</a>
                    </div>
                </div>
            </label>
            <label class="span-2" data-type-panel="EMPLOYEE" hidden>
                <span>Projects</span>
                <textarea name="translations[en][employee_projects]" rows="4" placeholder="Employee projects, one per line or as a short note."><?= e($form['translations']['en']['employee_projects'] ?? '') ?></textarea>
                <?php if (!$supportsEmployeeProjects): ?>
                    <small class="helper-note">To pole zacznie zapisywać się do bazy po uruchomieniu najnowszej migracji w <code>/admin/database</code>.</small>
                <?php endif; ?>
            </label>
            <label class="span-2">
                <span>Keywords</span>
                <input type="text" name="translations[en][seo_keywords]" value="<?= e($form['translations']['en']['seo_keywords'] ?? '') ?>" placeholder="keywords, separated, by comma" data-keywords-input="en">
                <small class="helper-note">Wpisuj słowa kluczowe po przecinku.</small>
                <div class="keyword-analysis" data-keyword-analysis="en"></div>
            </label>
            <label>
                <span>SEO title</span>
                <input type="text" name="translations[en][seo_title]" value="<?= e($form['translations']['en']['seo_title']) ?>">
            </label>
            <label>
                <span>Meta description</span>
                <textarea
                    name="translations[en][meta_description]"
                    rows="3"
                    data-seo-count
                    data-seo-label="Meta description EN"
                    data-seo-ideal="140-160"
                    data-locale="en"
                    data-role="meta"
                    data-keyword-meta="en"
                ><?= e($form['translations']['en']['meta_description']) ?></textarea>
                <small class="helper-note seo-counter" data-seo-output></small>
            </label>
            <label>
                <span>OG title</span>
                <input
                    type="text"
                    name="translations[en][og_title]"
                    value="<?= e($form['translations']['en']['og_title']) ?>"
                    data-seo-count
                    data-seo-label="OG title EN"
                    data-seo-ideal="55-70"
                >
                <small class="helper-note seo-counter" data-seo-output></small>
            </label>
            <label>
                <span>OG description</span>
                <textarea
                    name="translations[en][og_description]"
                    rows="3"
                    data-seo-count
                    data-seo-label="OG description EN"
                    data-seo-ideal="110-200"
                ><?= e($form['translations']['en']['og_description']) ?></textarea>
                <small class="helper-note seo-counter" data-seo-output></small>
            </label>
        </div>
    </section>

    <section class="admin-form-card">
        <h2 data-type-label data-default-label="Powiązane media" data-employee-label="Zdjęcia pracownika">Powiązane media</h2>
        <p class="helper-note" data-type-panel="EMPLOYEE" hidden>Dla pracownika wybierz zdjęcia. Pierwsze zaznaczone zdjęcie będzie traktowane jako główne.</p>
        <label>
            <span>Wybierz media</span>
            <select name="media_ids[]" multiple size="8">
                <?php foreach ($mediaAssets as $asset): ?>
                    <option value="<?= e((string) $asset['id']) ?>" <?= in_array((int) $asset['id'], $form['media_ids'], true) ? 'selected' : '' ?>>
                        #<?= e((string) $asset['id']) ?> <?= e($asset['title']) ?> (<?= e($asset['kind']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['media_ids'])): ?><small class="form-error"><?= e($errors['media_ids']) ?></small><?php endif; ?>
        </label>
    </section>

    <div class="admin-submit-row">
        <button class="button-primary" type="submit">Zapisz treść</button>
        <?php if (!$isNew): ?>
            <button
                class="button-danger"
                type="submit"
                formaction="/admin/content/<?= e((string) $form['id']) ?>/delete"
                formmethod="post"
                onclick="return confirm('Usunąć tę treść?');"
            >
                Usuń
            </button>
        <?php endif; ?>
    </div>
</form>
