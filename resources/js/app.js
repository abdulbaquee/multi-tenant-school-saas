

import 'bootstrap';
import 'bootstrap-icons/font/bootstrap-icons.css';

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-mark-all-present]');

    if (! button) {
        return;
    }

    document.querySelectorAll('[data-attendance-roster] input[type="radio"][value="present"]')
        .forEach((input) => {
            input.checked = true;
        });
});
