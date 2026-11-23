<?php
/**
 * includes/song_table.php - the table of songs with Play and heart buttons.
 * Used by listener/browse.php and listener/favorites.php so both look the same.
 *
 * Set these before including it:
 * $songs       rows with song_id, song_title, song_duration, audio_file,
 *              artist_name, album_name
 * $favoriteIds the user's favorite song ids as array KEYS (from array_flip),
 *              so isset($favoriteIds[5]) is a fast "is song 5 a favorite?"
 * $returnTo    'browse' or 'favorites': where to come back to without JavaScript
 * $q           (optional) the current search, so we come back to the same results
 */
?>
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead>
            <tr><th></th><th>Title</th><th>Artist</th><th>Album</th><th>Length</th><th><span class="visually-hidden">Favorite</span></th></tr>
        </thead>
        <tbody>
            <?php foreach ($songs as $song): ?>
                <?php $isFavorite = isset($favoriteIds[$song['song_id']]); ?>
                <tr>
                    <td>
                        <?php // The data- attributes tell assets/player.js what to play. ?>
                        <button type="button" class="btn btn-sm btn-success play-btn"
                                data-src="<?= escape(app_url($song['audio_file'])) ?>"
                                data-title="<?= escape($song['song_title']) ?>"
                                data-artist="<?= escape($song['artist_name']) ?>"
                                aria-label="Play <?= escape($song['song_title']) ?>">
                            <i class="bi bi-play-fill"></i>
                        </button>
                    </td>
                    <td><?= escape($song['song_title']) ?></td>
                    <td><?= escape($song['artist_name']) ?></td>
                    <td><?= escape($song['album_name'] ?? '—') ?></td>
                    <td><?= sprintf('%d:%02d', intdiv($song['song_duration'], 60), $song['song_duration'] % 60) ?></td>
                    <td>
                        <?php // A real form, so the heart works even without JavaScript. ?>
                        <form method="post" action="<?= escape(app_url('listener/favorite.php')) ?>" class="fav-form m-0">
                            <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
                            <input type="hidden" name="song_id" value="<?= (int) $song['song_id'] ?>">
                            <input type="hidden" name="return" value="<?= escape($returnTo) ?>">
                            <input type="hidden" name="q" value="<?= escape($q ?? '') ?>">
                            <button type="submit" class="btn btn-sm btn-link p-0 fs-5"
                                    aria-pressed="<?= $isFavorite ? 'true' : 'false' ?>"
                                    aria-label="<?= $isFavorite ? 'Remove from favorites' : 'Add to favorites' ?>">
                                <i class="bi <?= $isFavorite ? 'bi-heart-fill text-danger' : 'bi-heart text-body-secondary' ?>"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script src="<?= escape(app_url('assets/favorites.js')) ?>"></script>