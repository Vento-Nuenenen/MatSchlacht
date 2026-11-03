/**
 * The global #contentModal shows the title and HTML content of the element that opened it
 * (data-bs-title / data-bs-content), e.g. the content list of an item.
 */
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

        modal.querySelector('.modal-title').textContent =
            trigger.getAttribute('data-bs-title');
        modal.querySelector('.modal-body').innerHTML =
            trigger.getAttribute('data-bs-content');
    });
}
