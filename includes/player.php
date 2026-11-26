<?php
/**
 * includes/player.php - the player bar fixed to the bottom of the page.
 *
 * The hidden <audio> tag does the actual playing. Everything you see is
 * Bootstrap: navbar fixed-bottom, btn-group, form-range sliders and icons.
 * assets/player.js connects the buttons and sliders to the <audio> tag.
 *
 * Include it on a page that has .play-btn buttons, just before footer.php.
 */
?>
<div id="player" class="navbar fixed-bottom bg-body-tertiary border-top py-2">
    <div class="container d-flex flex-wrap align-items-center gap-3">
        <audio id="audio" preload="metadata"></audio>

        <?php // Now playing: title and artist ?>
        <div class="player-info">
            <div id="player-title" class="fw-semibold text-truncate">Nothing playing</div>
            <div id="player-artist" class="small text-body-secondary text-truncate">Pick a song to start</div>
        </div>

        <?php // Previous / Play-Pause / Next ?>
        <div class="btn-group" role="group" aria-label="Playback controls">
            <button type="button" id="btn-prev" class="btn btn-outline-secondary" aria-label="Previous">
                <i class="bi bi-skip-start-fill"></i>
            </button>
            <button type="button" id="btn-play" class="btn btn-success" aria-label="Play">
                <i class="bi bi-play-fill"></i>
            </button>
            <button type="button" id="btn-next" class="btn btn-outline-secondary" aria-label="Next">
                <i class="bi bi-skip-end-fill"></i>
            </button>
        </div>

        <?php // Seek bar with current time and total time ?>
        <div class="d-flex align-items-center gap-2 flex-grow-1">
            <span id="time-current" class="small text-body-secondary">0:00</span>
            <input type="range" id="seek" class="form-range" min="0" max="0" step="1" value="0" aria-label="Seek">
            <span id="time-total" class="small text-body-secondary">0:00</span>
        </div>

        <?php // Volume ?>
        <div class="player-volume d-flex align-items-center gap-2">
            <i class="bi bi-volume-up"></i>
            <input type="range" id="volume" class="form-range" min="0" max="1" step="0.05" value="1" aria-label="Volume">
        </div>
    </div>
</div>
<script src="<?= escape(app_url('assets/player.js')) ?>"></script>