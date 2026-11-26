// assets/favorites.js - makes the heart buttons work without reloading the page,
// so the song that is playing does not stop.
// It catches the heart form's submit, sends it in the background with fetch(),
// and swaps the icon when the server answers.

document.addEventListener('submit', async function (event) {
    const form = event.target.closest('.fav-form');
    if (!form) return;
    // some other form: leave it alone
    event.preventDefault();
    // stop the normal page reload
    const button = form.querySelector('button');
    button.disabled = true;
    // no double clicks while waiting
    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),                   // same fields as a normal submit
            headers: { 'Accept': 'application/json' }   // tells favorite.php to answer with JSON
        });
        if (!response.ok) throw new Error('Request failed');
        const data = await response.json();

        // Filled red heart = favorite, outline = not a favorite
        button.innerHTML = data.favorited
            ? '<i class="bi bi-heart-fill text-danger"></i>'
            : '<i class="bi bi-heart text-body-secondary"></i>';
        button.setAttribute('aria-pressed', data.favorited ? 'true' : 'false');
        button.setAttribute('aria-label', data.favorited ? 'Remove from favorites' : 'Add to favorites');
    } catch (error) {
        form.submit();
    } finally {
        button.disabled = false;
    }
});
