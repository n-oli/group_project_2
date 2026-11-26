// assets/theme.js - the dark/light mode button in the navbar.
// includes/header.php already applied the saved theme before the page was drawn;
// this file shows the right icon and switches the theme when the button is clicked.
// Bootstrap does the actual recoloring: every component follows the
// data-bs-theme="dark" or "light" attribute on the <html> tag.

document.addEventListener('DOMContentLoaded', function () {
    const root = document.documentElement;
    // the <html> tag
    const button = document.getElementById('theme-toggle');
    if (!button) return; // no button on this page
    // in dark mode, show a sun - in light mode, show a moon
    const showIcon = function (theme) {
        const next = theme === 'dark' ? 'light' : 'dark';
        button.innerHTML = '<i class="bi ' + (theme === 'dark' ? 'bi-sun-fill' : 'bi-moon-stars-fill') + '"></i>';
        button.setAttribute('aria-label', 'Switch to ' + next + ' mode');
        button.title = 'Switch to ' + next + ' mode';
    };
    showIcon(root.getAttribute('data-bs-theme'));

    button.addEventListener('click', function () {
        const theme = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-bs-theme', theme);
        try {
            localStorage.setItem('decibel-theme', theme); // same name header.php reads
        } catch (e) {}
        showIcon(theme);
    });
});
