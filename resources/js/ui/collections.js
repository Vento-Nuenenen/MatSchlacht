/**
 * The collection picker of the order forms (components/collection-picker). Adding a collection
 * raises the quantities of its items and makes them the minimum, removing it takes them off again.
 */
function quantityInput(itemId) {
    return document.querySelector(`input[name="items[${itemId}]"]`);
}

function adjustMinimum(input, difference) {
    input.dataset.minQty = Math.max(
        0,
        (parseInt(input.dataset.minQty, 10) || 0) + difference,
    );
}

function adjustItems(items, direction) {
    items.forEach((item) => {
        const input = quantityInput(item.id);

        if (!input) {
            return;
        }

        adjustMinimum(input, direction * item.quantity);
        input.value = Math.max(
            0,
            (parseInt(input.value, 10) || 0) + direction * item.quantity,
        );

        const row = input.closest('.item-row');

        if (row) {
            row.classList.toggle(
                'table-primary',
                parseInt(input.value, 10) > 0,
            );

            if (direction > 0) {
                row.style.display = '';
            }
        }
    });
}

function markFormChanged(element) {
    element
        .closest('form')
        ?.dispatchEvent(new window.Event('input', { bubbles: true }));
}

function createBadge(id, name, items) {
    const badge = document.createElement('span');
    badge.className = 'badge bg-info text-dark p-2';
    badge.dataset.collectionBadge = '';
    badge.dataset.items = JSON.stringify(items);
    badge.append(`${name} `);

    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'collections[]';
    hiddenInput.value = id;

    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'btn-close btn-close-white';
    removeButton.style.fontSize = '0.5rem';
    removeButton.dataset.collectionRemove = '';
    removeButton.setAttribute('aria-label', `${name} entfernen`);

    badge.append(hiddenInput, removeButton);

    return badge;
}

function addSelectedCollection(button) {
    const select = document.getElementById('collectionSelect');
    const option = select.options[select.selectedIndex];

    if (!option.value) {
        window.alert('Bitte wählen Sie eine Kollektion aus.');

        return;
    }

    const items = JSON.parse(option.dataset.items);

    adjustItems(items, 1);
    document
        .getElementById('collections-wrapper')
        .append(createBadge(option.value, option.dataset.name, items));
    document.getElementById('addedCollections').classList.remove('d-none');
    select.selectedIndex = 0;
    markFormChanged(button);
}

function removeCollection(button) {
    const badge = button.closest('[data-collection-badge]');
    const wrapper = document.getElementById('collections-wrapper');

    adjustItems(JSON.parse(badge.dataset.items || '[]'), -1);
    markFormChanged(button);
    badge.remove();

    if (wrapper.children.length === 0) {
        document.getElementById('addedCollections').classList.add('d-none');
    }
}

export function initCollectionPicker() {
    document.querySelectorAll('[data-collection-badge]').forEach((badge) => {
        JSON.parse(badge.dataset.items || '[]').forEach((item) => {
            const input = quantityInput(item.id);

            if (input) {
                adjustMinimum(input, item.quantity);
            }
        });
    });

    document.addEventListener('click', (event) => {
        const addButton = event.target.closest('[data-collection-add]');
        const removeButton = event.target.closest('[data-collection-remove]');

        if (addButton) {
            addSelectedCollection(addButton);
        } else if (removeButton) {
            removeCollection(removeButton);
        }
    });
}
