document.addEventListener('DOMContentLoaded', () => {
    const emitContentRefresh = () => {
        document.dispatchEvent(new Event('cms-content-refresh'));
    };

    initAutosubmitControls();
    initContentTypeControls();
    initTinyMce(emitContentRefresh);
    initContentEditorTools(emitContentRefresh);
    initContentTranslator(emitContentRefresh);
    initSettingsTranslator(emitContentRefresh);
    initMenuManager();
});

function initAutosubmitControls() {
    document.querySelectorAll('[data-autosubmit]').forEach((control) => {
        control.addEventListener('change', () => {
            const form = control.form;
            if (!form) {
                return;
            }

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();

                return;
            }

            form.submit();
        });
    });
}

function initTinyMce(emitContentRefresh) {
    if (!window.tinymce) {
        return;
    }

    tinymce.init({
        selector: '.wysiwyg',
        menubar: false,
        plugins: 'lists link code',
        toolbar: 'undo redo | bold italic | blocks | bullist numlist | link blockquote | code',
        block_formats: 'Akapit=p; Nagłówek 2=h2; Nagłówek 3=h3',
        height: 280,
        branding: false,
        promotion: false,
        setup: (editor) => {
            editor.on('input keyup change setcontent undo redo', emitContentRefresh);
        }
    });
}

function initContentEditorTools(emitContentRefresh) {
    const seoFields = Array.from(document.querySelectorAll('[data-seo-count]'));
    const keywordInputs = Array.from(document.querySelectorAll('[data-keywords-input]'));
    const editorTools = Array.from(document.querySelectorAll('[data-editor-tools]'));

    if (seoFields.length === 0 && keywordInputs.length === 0 && editorTools.length === 0) {
        return;
    }

    const stripHtml = (value) => {
        const probe = document.createElement('div');
        probe.innerHTML = value;

        return (probe.textContent || probe.innerText || '').trim();
    };

    const keywordCount = (text, keyword) => {
        const normalizedKeyword = keyword.trim().toLowerCase();
        if (!normalizedKeyword) {
            return 0;
        }

        const escaped = normalizedKeyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const matches = text.toLowerCase().match(new RegExp(escaped, 'g'));

        return matches ? matches.length : 0;
    };

    const updateSeoCounters = () => {
        seoFields.forEach((field) => {
            const output = field.parentElement?.querySelector('[data-seo-output]');
            if (!output) {
                return;
            }

            const label = field.dataset.seoLabel || 'Pole';
            const ideal = field.dataset.seoIdeal || '';
            const length = field.value.length;

            output.textContent = ideal !== ''
                ? `${label}: ${length} znaków · zalecane ${ideal}`
                : `${label}: ${length} znaków`;
        });
    };

    const bodyTextForLocale = (locale) => {
        const textarea = document.querySelector(`[data-keyword-body="${locale}"]`);
        if (!textarea) {
            return '';
        }

        const editorId = textarea.getAttribute('id');
        const editor = editorId && window.tinymce ? tinymce.get(editorId) : null;

        if (editor) {
            return editor.getContent({ format: 'text' }).trim();
        }

        return stripHtml(textarea.value);
    };

    const summaryTextForLocale = (locale) => {
        const field = document.querySelector(`[data-locale="${locale}"][data-role="summary"]`);

        return field ? field.value.trim() : '';
    };

    const metaTextForLocale = (locale) => {
        const field = document.querySelector(`[data-keyword-meta="${locale}"]`);

        return field ? field.value.trim() : '';
    };

    const updateKeywordAnalysis = () => {
        keywordInputs.forEach((input) => {
            const locale = input.dataset.keywordsInput;
            const container = locale ? document.querySelector(`[data-keyword-analysis="${locale}"]`) : null;

            if (!locale || !container) {
                return;
            }

            const keywords = input.value
                .split(',')
                .map((keyword) => keyword.trim())
                .filter(Boolean);

            if (keywords.length === 0) {
                container.innerHTML = '<div class="keyword-analysis-empty">Dodaj keywords po przecinku, aby zobaczyć analizę.</div>';
                return;
            }

            const bodyText = bodyTextForLocale(locale);
            const summaryText = summaryTextForLocale(locale);
            const metaText = metaTextForLocale(locale);

            const rows = keywords.map((keyword) => `
                <tr>
                    <td>${escapeHtml(keyword)}</td>
                    <td>${keywordCount(bodyText, keyword)}</td>
                    <td>${keywordCount(summaryText, keyword)}</td>
                    <td>${keywordCount(metaText, keyword)}</td>
                </tr>
            `).join('');

            container.innerHTML = `
                <table>
                    <thead>
                        <tr>
                            <th>Keyword</th>
                            <th>Body</th>
                            <th>Summary</th>
                            <th>Meta description</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            `;
        });
    };

    seoFields.forEach((field) => {
        field.addEventListener('input', updateSeoCounters);
    });

    keywordInputs.forEach((input) => {
        input.addEventListener('input', updateKeywordAnalysis);
    });

    document.querySelectorAll('[data-keyword-meta], [data-keyword-body], [data-role="summary"]').forEach((field) => {
        field.addEventListener('input', updateKeywordAnalysis);
    });

    document.addEventListener('cms-content-refresh', () => {
        updateSeoCounters();
        updateKeywordAnalysis();
    });

    editorTools.forEach((tool) => {
        const select = tool.querySelector('[data-image-select]');
        const button = tool.querySelector('[data-insert-image]');
        const targetEditor = tool.dataset.targetEditor;

        if (!select || !button || !targetEditor) {
            return;
        }

        button.addEventListener('click', () => {
            const option = select.selectedOptions[0];
            if (!option || !option.value) {
                return;
            }

            const imageUrl = option.value;
            const altText = option.dataset.alt || '';
            const caption = option.dataset.caption || '';
            const figureHtml = caption !== ''
                ? `<figure><img src="${escapeAttribute(imageUrl)}" alt="${escapeAttribute(altText)}"><figcaption>${escapeHtml(caption)}</figcaption></figure>`
                : `<figure><img src="${escapeAttribute(imageUrl)}" alt="${escapeAttribute(altText)}"></figure>`;

            const editor = window.tinymce ? tinymce.get(targetEditor) : null;
            if (editor) {
                editor.insertContent(figureHtml);
                editor.focus();
                emitContentRefresh();
                return;
            }

            const textarea = document.getElementById(targetEditor);
            if (!textarea) {
                return;
            }

            textarea.value += `\n${figureHtml}\n`;
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            emitContentRefresh();
        });
    });

    updateSeoCounters();
    updateKeywordAnalysis();
}

function initContentTypeControls() {
    const typeSelect = document.querySelector('[data-content-type-select]');
    if (!typeSelect) {
        return;
    }

    const sectionSelect = document.querySelector('[data-section-select]');
    const toggleNodes = (selector, type) => {
        document.querySelectorAll(selector).forEach((node) => {
            const allowedTypes = (node.dataset.typeVisibility || node.dataset.typePanel || '')
                .split(',')
                .map((item) => item.trim())
                .filter(Boolean);
            const isVisible = allowedTypes.includes(type);

            if ('hidden' in node) {
                node.hidden = !isVisible;
            }

            node.querySelectorAll('input, select, textarea, button').forEach((field) => {
                if (!field.closest('[data-editor-tools]')) {
                    field.disabled = !isVisible;
                }
            });
        });
    };

    const updateLabels = (type) => {
        document.querySelectorAll('[data-type-label]').forEach((node) => {
            const defaultLabel = node.dataset.defaultLabel || node.textContent || '';
            const employeeLabel = node.dataset.employeeLabel || defaultLabel;

            node.textContent = type === 'EMPLOYEE' ? employeeLabel : defaultLabel;
        });
    };

    const syncType = (forceSection = false) => {
        const type = typeSelect.value;
        toggleNodes('[data-type-panel]', type);
        toggleNodes('[data-type-visibility]', type);
        updateLabels(type);

        if (forceSection && type === 'EMPLOYEE' && sectionSelect) {
            sectionSelect.value = 'MUZEUM';
        }
    };

    typeSelect.addEventListener('change', () => {
        syncType(true);
    });

    syncType(false);
}

function initContentTranslator(emitContentRefresh) {
    const button = document.querySelector('[data-translate-content]');
    const form = button ? button.closest('form') : null;
    const status = form ? form.querySelector('[data-translate-status]') : null;

    if (!button || !form) {
        return;
    }

    const translatedFields = [
        'slug',
        'title',
        'summary',
        'body',
        'employee_projects',
        'seo_keywords',
        'seo_title',
        'meta_description',
        'og_title',
        'og_description'
    ];

    const fieldSelector = (name) => `[name="${name.replace(/\\/g, '\\\\').replace(/"/g, '\\"')}"]`;
    const fieldByName = (name) => form.querySelector(fieldSelector(name));
    const stripHtml = (value) => {
        const probe = document.createElement('div');
        probe.innerHTML = value;

        return (probe.textContent || probe.innerText || '').trim();
    };

    const editorForField = (field) => {
        if (!field) {
            return null;
        }

        const editorId = field.getAttribute('id');

        return editorId && window.tinymce ? tinymce.get(editorId) : null;
    };

    const readField = (name) => {
        const field = fieldByName(name);
        if (!field) {
            return '';
        }

        const editor = editorForField(field);
        if (editor) {
            return editor.getContent();
        }

        return field.value;
    };

    const writeField = (name, value) => {
        const field = fieldByName(name);
        if (!field) {
            return;
        }

        const nextValue = value || '';
        const editor = editorForField(field);

        field.value = nextValue;

        if (editor) {
            editor.setContent(nextValue);
            editor.save();
        }

        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const hasEnglishContent = () => translatedFields.some((field) => {
        const value = readField(`translations[en][${field}]`);

        return field === 'body' ? stripHtml(value) !== '' : value.trim() !== '';
    });

    const setStatus = (message, state = '') => {
        if (!status) {
            return;
        }

        status.textContent = message;
        status.hidden = message === '';
        status.classList.remove('is-loading', 'is-success', 'is-error');

        if (state !== '') {
            status.classList.add(`is-${state}`);
        }
    };

    button.addEventListener('click', async () => {
        if (hasEnglishContent() && !window.confirm('Pola EN zawierają już treść. Nadpisać je tłumaczeniem z PL?')) {
            return;
        }

        const payload = new FormData();
        const csrfToken = form.querySelector('input[name="_token"]');
        const contentType = fieldByName('content_type');
        const sectionKey = fieldByName('section_key');
        const originalLabel = button.textContent;

        payload.append('_token', csrfToken ? csrfToken.value : '');
        payload.append('content_type', contentType ? contentType.value : 'PAGE');
        payload.append('section_key', sectionKey ? sectionKey.value : 'MUZEUM');

        translatedFields.forEach((field) => {
            payload.append(`source[${field}]`, readField(`translations[pl][${field}]`));
        });

        button.disabled = true;
        button.textContent = button.dataset.loadingLabel || 'Tłumaczę...';
        setStatus('Tłumaczę treść PL na EN...', 'loading');

        try {
            const response = await fetch(button.dataset.translateUrl || '/admin/content/translate', {
                method: 'POST',
                body: payload,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok || !result.ok || !result.translation) {
                throw new Error(result.message || 'Nie udało się przetłumaczyć treści.');
            }

            translatedFields.forEach((field) => {
                writeField(`translations[en][${field}]`, result.translation[field] || '');
            });

            emitContentRefresh();
            setStatus(
                result.model
                    ? `Przetłumaczono pola PL do EN (${result.model}).`
                    : 'Przetłumaczono pola PL do EN.',
                'success'
            );
        } catch (error) {
            setStatus(
                error instanceof Error ? error.message : 'Nie udało się przetłumaczyć treści.',
                'error'
            );
        } finally {
            button.disabled = false;
            button.textContent = originalLabel;
        }
    });
}

function initSettingsTranslator(emitContentRefresh) {
    const button = document.querySelector('[data-translate-settings]');
    const form = button ? button.closest('form') : null;
    const status = form ? form.querySelector('[data-translate-settings-status]') : null;

    if (!button || !form) {
        return;
    }

    const translatedFields = [
        'museum_name',
        'opening_hours',
        'organization_description',
        'homepage_title',
        'homepage_lead',
        'homepage_intro',
        'visit_note',
        'default_seo_title',
        'default_meta_description',
        'default_og_title',
        'default_og_description'
    ];

    const fieldByName = (name) => form.querySelector(`[name="${name.replace(/"/g, '\\"')}"]`);
    const editorForField = (field) => {
        const editorId = field ? field.getAttribute('id') : null;
        return editorId && window.tinymce ? tinymce.get(editorId) : null;
    };
    const stripHtml = (value) => {
        const probe = document.createElement('div');
        probe.innerHTML = value;
        return (probe.textContent || probe.innerText || '').trim();
    };
    const readField = (name) => {
        const field = fieldByName(name);
        if (!field) {
            return '';
        }

        const editor = editorForField(field);
        return editor ? editor.getContent() : field.value;
    };
    const writeField = (name, value) => {
        const field = fieldByName(name);
        if (!field) {
            return;
        }

        field.value = value || '';
        const editor = editorForField(field);
        if (editor) {
            editor.setContent(value || '');
            editor.save();
        }

        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
    };
    const hasEnglishContent = () => translatedFields.some((field) => stripHtml(readField(`translations[en][${field}]`)) !== '');
    const setStatus = (message, state = '') => {
        if (!status) {
            return;
        }

        status.textContent = message;
        status.hidden = message === '';
        status.classList.remove('is-loading', 'is-success', 'is-error');
        if (state) {
            status.classList.add(`is-${state}`);
        }
    };

    button.addEventListener('click', async () => {
        if (hasEnglishContent() && !window.confirm('Pola EN zawierają już treść. Nadpisać je tłumaczeniem z PL?')) {
            return;
        }

        const payload = new FormData();
        const csrfToken = form.querySelector('input[name="_token"]');
        const originalLabel = button.textContent;

        payload.append('_token', csrfToken ? csrfToken.value : '');
        translatedFields.forEach((field) => {
            payload.append(`source[${field}]`, readField(`translations[pl][${field}]`));
        });

        button.disabled = true;
        button.textContent = button.dataset.loadingLabel || 'Tłumaczę...';
        setStatus('Tłumaczę treści PL na EN...', 'loading');

        try {
            const response = await fetch(button.dataset.translateUrl || '/admin/settings/translate', {
                method: 'POST',
                body: payload,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json().catch(() => ({}));
            if (!response.ok || !result.ok || !result.translation) {
                throw new Error(result.message || 'Nie udało się przetłumaczyć treści.');
            }

            translatedFields.forEach((field) => {
                writeField(`translations[en][${field}]`, result.translation[field] || '');
            });

            emitContentRefresh();
            setStatus(result.model ? `Przetłumaczono pola PL do EN (${result.model}).` : 'Przetłumaczono pola PL do EN.', 'success');
        } catch (error) {
            setStatus(error instanceof Error ? error.message : 'Nie udało się przetłumaczyć treści.', 'error');
        } finally {
            button.disabled = false;
            button.textContent = originalLabel;
        }
    });
}

function initMenuManager() {
    const menuManager = document.querySelector('[data-menu-manager]');
    if (!menuManager) {
        return;
    }

    const reorderForm = document.getElementById('menu-reorder-form');
    const payloadInput = reorderForm?.querySelector('input[name="order_payload"]');
    if (!reorderForm || !payloadInput) {
        return;
    }

    let draggedItem = null;

    const updatePayload = () => {
        const rows = [];

        const walkTree = (tree, parentId = null) => {
            const items = Array.from(tree.children).filter((node) => node.matches('[data-menu-item]'));
            items.forEach((item, index) => {
                const itemId = Number(item.dataset.id || 0);
                if (!itemId) {
                    return;
                }

                rows.push({
                    id: itemId,
                    parent_id: parentId,
                    sort_order: (index + 1) * 10
                });

                const subtree = item.querySelector(':scope > .menu-tree');
                if (subtree) {
                    walkTree(subtree, itemId);
                }
            });
        };

        const rootTree = menuManager.querySelector(':scope > .menu-tree');
        if (rootTree) {
            walkTree(rootTree, null);
        }

        payloadInput.value = JSON.stringify(rows);
    };

    const clearDropMarkers = () => {
        menuManager.querySelectorAll('.menu-tree-item').forEach((item) => {
            item.classList.remove('is-drop-before', 'is-drop-after');
        });

        menuManager.querySelectorAll('.menu-tree, .menu-tree-dropzone, .menu-tree-root-dropzone').forEach((node) => {
            node.classList.remove('is-drop-target');
        });
    };

    const canDropIntoTree = (tree) => {
        if (!draggedItem || !tree) {
            return false;
        }

        if (tree.closest('[data-menu-item]') === draggedItem) {
            return false;
        }

        if (draggedItem.contains(tree)) {
            return false;
        }

        return true;
    };

    const moveIntoTree = (tree) => {
        if (!draggedItem || !canDropIntoTree(tree)) {
            return;
        }

        tree.appendChild(draggedItem);
        clearDropMarkers();
        updatePayload();
    };

    menuManager.querySelectorAll('.menu-drag-handle').forEach((handle) => {
        handle.addEventListener('dragstart', (event) => {
            draggedItem = handle.closest('[data-menu-item]');
            if (!draggedItem) {
                return;
            }

            draggedItem.classList.add('is-dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', draggedItem.dataset.id || '');
        });

        handle.addEventListener('dragend', () => {
            if (draggedItem) {
                draggedItem.classList.remove('is-dragging');
            }

            draggedItem = null;
            clearDropMarkers();
            updatePayload();
        });
    });

    menuManager.querySelectorAll('[data-menu-item]').forEach((item) => {
        item.addEventListener('dragover', (event) => {
            if (!draggedItem || draggedItem === item) {
                return;
            }

            const sourceTree = draggedItem.parentElement;
            const targetTree = item.parentElement;
            if (!sourceTree || !targetTree || sourceTree !== targetTree) {
                return;
            }

            event.preventDefault();
            clearDropMarkers();

            const bounds = item.getBoundingClientRect();
            const insertAfter = event.clientY > bounds.top + bounds.height / 2;
            item.classList.add(insertAfter ? 'is-drop-after' : 'is-drop-before');
        });

        item.addEventListener('dragleave', () => {
            item.classList.remove('is-drop-before', 'is-drop-after');
        });

        item.addEventListener('drop', (event) => {
            if (!draggedItem || draggedItem === item) {
                return;
            }

            const sourceTree = draggedItem.parentElement;
            const targetTree = item.parentElement;
            if (!sourceTree || !targetTree || sourceTree !== targetTree) {
                return;
            }

            event.preventDefault();

            const bounds = item.getBoundingClientRect();
            const insertAfter = event.clientY > bounds.top + bounds.height / 2;

            if (insertAfter) {
                item.insertAdjacentElement('afterend', draggedItem);
            } else {
                item.insertAdjacentElement('beforebegin', draggedItem);
            }

            clearDropMarkers();
            updatePayload();
        });
    });

    menuManager.querySelectorAll('.menu-tree').forEach((tree) => {
        tree.addEventListener('dragover', (event) => {
            if (!canDropIntoTree(tree)) {
                return;
            }

            event.preventDefault();
            clearDropMarkers();
            tree.classList.add('is-drop-target');
        });

        tree.addEventListener('dragleave', (event) => {
            if (!tree.contains(event.relatedTarget)) {
                tree.classList.remove('is-drop-target');
            }
        });

        tree.addEventListener('drop', (event) => {
            if (!canDropIntoTree(tree)) {
                return;
            }

            if (event.target.closest('[data-menu-item]')) {
                return;
            }

            event.preventDefault();
            moveIntoTree(tree);
        });
    });

    menuManager.querySelectorAll('.menu-tree-dropzone').forEach((zone) => {
        const tree = zone.querySelector(':scope > .menu-tree');
        if (!tree) {
            return;
        }

        zone.addEventListener('dragover', (event) => {
            if (!canDropIntoTree(tree)) {
                return;
            }

            event.preventDefault();
            clearDropMarkers();
            zone.classList.add('is-drop-target');
        });

        zone.addEventListener('dragleave', (event) => {
            if (!zone.contains(event.relatedTarget)) {
                zone.classList.remove('is-drop-target');
            }
        });

        zone.addEventListener('drop', (event) => {
            if (!canDropIntoTree(tree)) {
                return;
            }

            event.preventDefault();
            moveIntoTree(tree);
        });
    });

    const rootDropzone = menuManager.querySelector('.menu-tree-root-dropzone');
    const rootTree = menuManager.querySelector(':scope > .menu-tree');
    if (rootDropzone && rootTree) {
        rootDropzone.addEventListener('dragover', (event) => {
            if (!canDropIntoTree(rootTree)) {
                return;
            }

            event.preventDefault();
            clearDropMarkers();
            rootDropzone.classList.add('is-drop-target');
        });

        rootDropzone.addEventListener('dragleave', (event) => {
            if (!rootDropzone.contains(event.relatedTarget)) {
                rootDropzone.classList.remove('is-drop-target');
            }
        });

        rootDropzone.addEventListener('drop', (event) => {
            if (!canDropIntoTree(rootTree)) {
                return;
            }

            event.preventDefault();
            moveIntoTree(rootTree);
        });
    }

    reorderForm.addEventListener('submit', () => {
        updatePayload();
    });

    updatePayload();
}

function escapeHtml(value) {
    return value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function escapeAttribute(value) {
    return escapeHtml(value);
}
