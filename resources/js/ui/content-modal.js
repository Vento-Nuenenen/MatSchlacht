/**
 * The global #contentModal shows the list of the element that opened it (data-content-title,
 * data-content-lines). The values are inserted as text and can never run as HTML.
 */
function listItem({ label, text }) {
    const item = document.createElement('li');
    const strong = document.createElement('strong');

    strong.textContent = label;
    item.append(strong, ` ${text}`);

    return item;
}

export function initContentModal() {
    const modal = document.getElementById('contentModal');

    if (!modal) {
        return;
    }

    modal.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;

        if (!trigger) {
            return;
        }

        const list = document.createElement('ul');

        list.className = 'list-unstyled mb-0';
        list.append(
            ...JSON.parse(trigger.dataset.contentLines || '[]').map(listItem),
        );

        modal.querySelector('.modal-title').textContent =
            trigger.dataset.contentTitle;
        modal.querySelector('.modal-body').replaceChildren(list);
    });
}
