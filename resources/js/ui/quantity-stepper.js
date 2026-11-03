/**
 * Plus / minus buttons next to a number input: <button data-qty-step="1"> inside .qty-stepper.
 * Triggers an input event, so existing handlers (limits, highlighting) keep working.
 */
export function initQuantitySteppers() {
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-qty-step]');

        if (!button) {
            return;
        }

        const input = button
            .closest('.qty-stepper')
            ?.querySelector('input[type=number]');

        if (!input || input.disabled || input.readOnly) {
            return;
        }

        const step = parseInt(button.dataset.qtyStep, 10) || 0;
        const min = input.min !== '' ? parseInt(input.min, 10) : 0;
        const max = input.max !== '' ? parseInt(input.max, 10) : Infinity;
        const next = Math.min(
            max,
            Math.max(min, (parseInt(input.value, 10) || 0) + step),
        );

        input.value = next;
        input.dispatchEvent(new window.Event('input', { bubbles: true }));
        input.dispatchEvent(new window.Event('change', { bubbles: true }));
    });
}
