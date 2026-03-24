document.addEventListener('DOMContentLoaded', () => {
    const accordionRoot = document.querySelector('[data-accordion-root]');

    if (!accordionRoot) {
        return;
    }

    const collapseSectionRows = (exceptRow = null) => {
        document.querySelectorAll('[data-section-row]').forEach((row) => {
            const submenu = row.querySelector('[data-section-submenu]');
            const toggle = row.querySelector('[data-section-toggle]');

            if (!submenu || row === exceptRow) {
                return;
            }

            row.classList.remove('is-active');
            submenu.classList.remove('is-open');

            if (toggle) {
                toggle.classList.remove('is-active');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    };

    const initSectionRows = () => {
        const activeRow = document.querySelector('.accordion-row.is-active');

        if (!activeRow) {
            collapseSectionRows();

            const hash = window.location.hash;
            if (hash.startsWith('#section-')) {
                const hashRow = document.querySelector(hash);
                const hashSubmenu = hashRow?.querySelector('[data-section-submenu]');
                const hashToggle = hashRow?.querySelector('[data-section-toggle]');

                if (hashRow && hashSubmenu) {
                    hashRow.classList.add('is-active');
                    hashSubmenu.classList.add('is-open');

                    if (hashToggle) {
                        hashToggle.classList.add('is-active');
                        hashToggle.setAttribute('aria-expanded', 'true');
                    }
                }
            }

            return;
        }

        const submenu = activeRow.querySelector('[data-section-submenu]');
        const toggle = activeRow.querySelector('[data-section-toggle]');

        collapseSectionRows(activeRow);

        if (submenu) {
            submenu.classList.add('is-open');
        }

        if (toggle) {
            toggle.classList.add('is-active');
            toggle.setAttribute('aria-expanded', 'true');
        }
    };

    const syncHead = (doc) => {
        document.title = doc.title;

        [
            'meta[name="description"]',
            'meta[property="og:title"]',
            'meta[property="og:description"]',
            'meta[property="og:url"]',
            'meta[property="og:image"]',
            'link[rel="canonical"]',
            'style#theme-font-faces',
            'style#theme-variables',
        ].forEach((selector) => {
            const next = doc.querySelector(selector);
            const current = document.head.querySelector(selector);

            if (!next && current) {
                current.remove();
                return;
            }

            if (!next) {
                return;
            }

            if (current) {
                if (selector.startsWith('style#')) {
                    if (current.textContent !== next.textContent) {
                        current.textContent = next.textContent;
                    }

                    Array.from(next.attributes).forEach((attribute) => {
                        current.setAttribute(attribute.name, attribute.value);
                    });

                    return;
                }

                Array.from(next.attributes).forEach((attribute) => {
                    current.setAttribute(attribute.name, attribute.value);
                });
                return;
            }

            document.head.append(next.cloneNode(true));
        });
    };

    const replaceAccordion = (doc, url, pushState) => {
        const incoming = doc.querySelector('[data-accordion-root]');
        const current = document.querySelector('[data-accordion-root]');

        if (!incoming || !current) {
            window.location.href = url;
            return;
        }

        syncHead(doc);
        current.replaceWith(incoming);

        if (pushState) {
            window.history.pushState({}, '', url);
        }

        initSectionRows();

        const activeRow = document.querySelector('.accordion-row.is-active');
        if (activeRow) {
            activeRow.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    const navigate = async (url, pushState) => {
        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok || response.redirected) {
                window.location.href = response.redirected ? response.url : url;
                return;
            }

            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            replaceAccordion(doc, url, pushState);
        } catch (error) {
            window.location.href = url;
        }
    };

    document.addEventListener('click', (event) => {
        const toggle = event.target.closest('[data-section-toggle]');

        if (toggle) {
            const row = toggle.closest('[data-section-row]');
            const submenu = row?.querySelector('[data-section-submenu]');

            if (!row || !submenu) {
                return;
            }

            const isOpen = submenu.classList.contains('is-open');
            collapseSectionRows();

            if (!isOpen) {
                row.classList.add('is-active');
                submenu.classList.add('is-open');
                toggle.classList.add('is-active');
                toggle.setAttribute('aria-expanded', 'true');
            }

            return;
        }

        const singleToggle = event.target.closest('[data-section-single-toggle]');

        if (singleToggle) {
            const row = singleToggle.closest('[data-section-row]');
            const targetUrl = row?.classList.contains('is-active')
                ? singleToggle.getAttribute('data-close-url')
                : singleToggle.getAttribute('data-open-url');

            if (targetUrl) {
                navigate(new URL(targetUrl, window.location.origin).toString(), true);
            }

            return;
        }

        const link = event.target.closest('a[data-accordion-nav]');

        if (!link) {
            return;
        }

        if (
            event.defaultPrevented ||
            event.button !== 0 ||
            event.metaKey ||
            event.ctrlKey ||
            event.shiftKey ||
            event.altKey
        ) {
            return;
        }

        const url = new URL(link.href, window.location.origin);

        if (url.origin !== window.location.origin) {
            return;
        }

        event.preventDefault();
        navigate(url.toString(), true);
    });

    window.addEventListener('popstate', () => {
        navigate(window.location.href, false);
    });

    initSectionRows();
});
