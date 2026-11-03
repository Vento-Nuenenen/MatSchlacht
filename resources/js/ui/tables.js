/**
 * Tables inside .table-responsive are shown as stacked entries on smartphones (see
 * resources/sass/_tables.scss). Every cell gets the text of its column header as label; the
 * first text cell becomes the title of the entry and cells with only buttons become the action
 * row. Tables with .table-no-stack keep their table layout.
 */
function headerLabels(table) {
    const headerRow = table.tHead?.rows[0];

    if (!headerRow) {
        return [];
    }

    const labels = [];

    Array.from(headerRow.cells).forEach((cell) => {
        const span = cell.colSpan || 1;
        const text = cell.textContent.trim();

        for (let i = 0; i < span; i++) {
            labels.push(text);
        }
    });

    return labels;
}

function cellRole(cell) {
    const hasControls = cell.querySelector(
        'input:not([type=hidden]), select, textarea',
    );
    const text = cell.textContent.replace(/\s+/g, ' ').trim();
    const buttons = cell.querySelectorAll('.btn, button, form');

    if (cell.querySelector('input[type=checkbox]') && text === '') {
        return 'select';
    }

    if (buttons.length > 0 && !hasControls) {
        const textWithoutButtons = Array.from(cell.childNodes)
            .filter(
                (node) =>
                    !(
                        node.nodeType === window.Node.ELEMENT_NODE &&
                        node.matches('.btn, button, form, .d-flex, div')
                    ),
            )
            .map((node) => node.textContent)
            .join('')
            .trim();

        if (textWithoutButtons === '') {
            return 'actions';
        }
    }

    return null;
}

export function stackTables(root = document) {
    root.querySelectorAll(
        '.table-responsive > table.table, .card-body.table-responsive > table.table',
    ).forEach((table) => {
        if (
            table.classList.contains('table-no-stack') ||
            table.dataset.stacked === 'true'
        ) {
            return;
        }

        const labels = headerLabels(table);
        table.classList.add('table-stack');
        table.dataset.stacked = 'true';

        Array.from(table.tBodies).forEach((tbody) => {
            Array.from(tbody.rows).forEach((row) => {
                let column = 0;
                let hasTitle = false;

                Array.from(row.cells).forEach((cell) => {
                    const role = cell.colSpan > 1 ? null : cellRole(cell);

                    if (role) {
                        cell.dataset.cellRole = role;
                    } else if (
                        !hasTitle &&
                        cell.colSpan === 1 &&
                        cell.textContent.trim() !== ''
                    ) {
                        cell.dataset.cellRole = 'title';
                        hasTitle = true;
                    }

                    if (labels[column] && !cell.hasAttribute('data-label')) {
                        cell.dataset.label = labels[column];
                    }

                    column += cell.colSpan || 1;
                });
            });
        });
    });
}

/**
 * Click on a .sortable-header sorts the rows of its table (data-sort-value wins over the text).
 */
export function initSortableTables() {
    const cellValue = (row, index) => {
        const cell = row.children[index];

        return cell
            ? cell.dataset.sortValue || cell.innerText || cell.textContent
            : '';
    };

    const comparer = (index, ascending) => (a, b) => {
        const first = cellValue(ascending ? a : b, index);
        const second = cellValue(ascending ? b : a, index);

        return first !== '' && second !== '' && !isNaN(first) && !isNaN(second)
            ? first - second
            : first.toString().localeCompare(second, 'de', { numeric: true });
    };

    document.addEventListener('click', (event) => {
        const header = event.target.closest('th.sortable-header');

        if (!header) {
            return;
        }

        const table = header.closest('table');
        const tbody = table.querySelector('tbody');
        const index = Array.from(header.parentNode.children).indexOf(header);
        const isAscending = header.classList.contains('sort-asc');

        header.parentNode
            .querySelectorAll('th')
            .forEach((th) => th.classList.remove('sort-asc', 'sort-desc'));
        header.classList.add(isAscending ? 'sort-desc' : 'sort-asc');

        Array.from(tbody.querySelectorAll('tr'))
            .sort(comparer(index, !isAscending))
            .forEach((row) => tbody.appendChild(row));
    });
}
