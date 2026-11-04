import './bootstrap';
import { initCollectionPicker } from './ui/collections';
import { initContentModal } from './ui/content-modal';
import { initMessages } from './ui/messages';
import { initPerPage } from './ui/per-page';
import { initQuantitySteppers } from './ui/quantity-stepper';
import { initSortableTables, stackTables } from './ui/tables';
import { initTheme } from './ui/theme';

initTheme();
initSortableTables();
initQuantitySteppers();
initPerPage();

const onReady = (callback) =>
    document.readyState === 'loading'
        ? document.addEventListener('DOMContentLoaded', callback)
        : callback();

onReady(() => {
    stackTables();
    initContentModal();
    initCollectionPicker();
    initMessages();
});
