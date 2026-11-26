<?php
/**
 * includes/footer.php - the bottom of every page.
 * Closes the <main> that header.php opened, prints the footer line and loads
 * Bootstrap's JavaScript (needed for the collapsing navbar on phones).
 * Include it as the last line of every page that included header.php.
 */
?>
</main>

<footer class="border-top py-3 text-center text-body-secondary small">
    Decibel &middot; Team 2 Music Player &middot; CSC 235
</footer>

<script src="<?= escape(app_url('assets/theme.js')) ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
