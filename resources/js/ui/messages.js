/**
 * The two kinds of messages of the app. Input errors appear at their field, in the form that was
 * submitted. Everything else is a toast, and so is an input error without a visible field, so
 * that no message can get lost.
 */
const SUCCESS_DELAY = 5000;

function showToast(element) {
    const isError = element.classList.contains('app-toast-danger');

    new window.bootstrap.Toast(element, {
        autohide: !isError,
        delay: SUCCESS_DELAY,
    }).show();
}

function addErrorToast(message) {
    const toast = document
        .getElementById('app-toast-template')
        .content.firstElementChild.cloneNode(true);

    toast.querySelector('.toast-body').textContent = message;
    document.getElementById('app-toasts').append(toast);
    showToast(toast);
}

/**
 * The names a field can have for an error key: "items.5" is items[5], "files.0" is files[].
 */
function fieldNames(key) {
    const [base, ...parts] = key.split('.');
    const nested = base + parts.map((part) => `[${part}]`).join('');
    const withoutLast =
        base +
        parts
            .slice(0, -1)
            .map((part) => `[${part}]`)
            .join('');

    return [key, nested, `${withoutLast}[]`, `${nested}[]`];
}

function isVisible(element) {
    return element.getClientRects().length > 0;
}

function findTarget(key, scope) {
    const anchor = scope.querySelector(
        `[data-error-for="${window.CSS.escape(key)}"]`,
    );

    if (anchor) {
        return anchor;
    }

    const fields = fieldNames(key).flatMap((name) =>
        Array.from(
            scope.querySelectorAll(
                `[name="${window.CSS.escape(name)}"]:not([type=hidden])`,
            ),
        ),
    );

    return fields.find(isVisible) ?? null;
}

function showAtField(target, messages, index) {
    const feedback = document.createElement('div');

    feedback.className = 'invalid-feedback d-block';
    feedback.id = `field-error-${index}`;
    feedback.textContent = messages.join(' ');

    if (target.matches('[data-error-for]')) {
        target.append(feedback);

        return;
    }

    target.classList.add('is-invalid');
    target.setAttribute('aria-invalid', 'true');
    target.setAttribute(
        'aria-describedby',
        [target.getAttribute('aria-describedby'), feedback.id]
            .filter(Boolean)
            .join(' '),
    );
    (target.closest('.input-group, .qty-stepper, .form-check') ?? target).after(
        feedback,
    );

    target.addEventListener(
        'input',
        () => {
            target.classList.remove('is-invalid');
            target.removeAttribute('aria-invalid');
            feedback.remove();
        },
        { once: true },
    );
}

function showFormErrors() {
    const data = document.getElementById('form-errors');

    if (!data) {
        return;
    }

    const form = data.dataset.form
        ? document.querySelector(
              `form[action="${window.CSS.escape(data.dataset.form)}"]`,
          )
        : null;
    let firstTarget = null;

    Object.entries(JSON.parse(data.textContent)).forEach(
        ([key, messages], index) => {
            const target = findTarget(key, form ?? document);

            if (target) {
                showAtField(target, messages, index);
                firstTarget ??= target;
            } else {
                messages.forEach(addErrorToast);
            }
        },
    );

    firstTarget?.scrollIntoView({ block: 'center' });
    firstTarget?.focus({ preventScroll: true });
}

export function initMessages() {
    document.querySelectorAll('#app-toasts .toast').forEach(showToast);
    showFormErrors();

    // Tells the server which form was sent, several forms of a page can have the same field names.
    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (
            form.method.toLowerCase() !== 'post' ||
            form.querySelector('input[name="_form"]')
        ) {
            return;
        }

        const marker = document.createElement('input');

        marker.type = 'hidden';
        marker.name = '_form';
        marker.value = form.getAttribute('action');
        form.append(marker);
    });
}
