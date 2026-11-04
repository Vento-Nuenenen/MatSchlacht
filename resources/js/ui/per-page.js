import { stackTables } from './tables';

/**
 * Paginated lists change without a page reload: the «Pro Seite» field and the page links fetch
 * the list and only replace the content area. Whenever that fails, the page loads normally.
 *
 * The choice of the field is kept per device in a cookie, «Automatisch» removes it again and
 * the default of the screen size applies.
 */
const COOKIE_NAME = 'matschlacht_per_page';

function storeChoice(value) {
    const lifetime = value === '' ? 0 : 60 * 60 * 24 * 365;

    document.cookie = `${COOKIE_NAME}=${value}; path=/; max-age=${lifetime}; SameSite=Lax`;
}

function urlOnFirstPage() {
    const url = new window.URL(window.location.href);

    Array.from(url.searchParams.keys())
        .filter((name) => name === 'page' || name.endsWith('_page'))
        .forEach((name) => url.searchParams.delete(name));

    return url.toString();
}

async function fetchContent(url) {
    const response = await window.fetch(url, { credentials: 'same-origin' });

    // A redirect means the list is gone for this user, e.g. because the session expired.
    if (!response.ok || response.redirected) {
        throw new Error(`Unexpected response for ${url}`);
    }

    const page = new window.DOMParser().parseFromString(
        await response.text(),
        'text/html',
    );
    const content = page.getElementById('main-content');

    if (!content) {
        throw new Error(`No content in ${url}`);
    }

    return content;
}

async function showList(
    url,
    { addToHistory = true, focusSelector = null, keepScroll = false } = {},
) {
    const main = document.getElementById('main-content');

    main.setAttribute('aria-busy', 'true');

    try {
        const content = await fetchContent(url);

        main.replaceChildren(...content.childNodes);
        stackTables(main);

        if (addToHistory && url !== window.location.href) {
            window.history.pushState({ list: true }, '', url);
        }

        if (!keepScroll) {
            window.scrollTo({ top: 0 });
        }

        main.querySelector(focusSelector)?.focus({ preventScroll: true });
    } catch {
        window.location.assign(url);
    } finally {
        main.removeAttribute('aria-busy');
    }
}

export function initPerPage() {
    document.addEventListener('change', (event) => {
        const select = event.target.closest('[data-per-page]');

        if (select) {
            storeChoice(select.value);
            showList(urlOnFirstPage(), {
                focusSelector: '[data-per-page]',
                keepScroll: true,
            });
        }
    });

    document.addEventListener('click', (event) => {
        const link = event.target.closest('.app-pagination a.page-link');
        const isPlainClick =
            event.button === 0 &&
            !event.ctrlKey &&
            !event.metaKey &&
            !event.shiftKey;

        if (!link || !isPlainClick || link.getAttribute('href') === '#') {
            return;
        }

        event.preventDefault();
        showList(link.href, { focusSelector: '[data-per-page]' });
    });

    window.addEventListener('popstate', () => {
        if (document.querySelector('.app-pagination')) {
            showList(window.location.href, { addToHistory: false });
        }
    });
}
