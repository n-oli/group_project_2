// assets/player.js - connects the Bootstrap player bar to the hidden <audio> tag.
// The <audio> tag does the playing; this script only tells it what to do
// and keeps the buttons, sliders and text in step with it.

document.addEventListener('DOMContentLoaded', function () {
    const audio    = document.getElementById('audio');
    const playBtn  = document.getElementById('btn-play');
    const prevBtn  = document.getElementById('btn-prev');
    const nextBtn  = document.getElementById('btn-next');
    const seek     = document.getElementById('seek');
    const volume   = document.getElementById('volume');
    const titleEl  = document.getElementById('player-title');
    const artistEl = document.getElementById('player-artist');
    const timeCur  = document.getElementById('time-current');
    const timeTot  = document.getElementById('time-total');

    // The queue is every Play button on the page, in order.
    const queue = Array.from(document.querySelectorAll('.play-btn'));
    let current = -1;       // index of the song in the queue; -1 = nothing loaded
    let dragging = false;   // true while the user is dragging the seek bar

    // 215 -> "3:35"
    function formatTime(seconds) {
        if (!isFinite(seconds)) return '0:00';
        const s = Math.floor(seconds);
        return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
    }

    // Show a play or pause icon on the bar's button and on the current row's button.
    function setIcons(playing) {
        const icon = '<i class="bi ' + (playing ? 'bi-pause-fill' : 'bi-play-fill') + '"></i>';
        playBtn.innerHTML = icon;
        playBtn.setAttribute('aria-label', playing ? 'Pause' : 'Play');
        if (current >= 0) queue[current].innerHTML = icon;
    }

    // Load song number "index" from the queue and start playing it.
    function playSong(index) {
        if (index < 0 || index >= queue.length) return;

        // Un-highlight the old row and reset its button icon
        if (current >= 0) {
            queue[current].closest('tr').classList.remove('table-active');
            queue[current].innerHTML = '<i class="bi bi-play-fill"></i>';
        }

        current = index;
        const btn = queue[current];
        btn.closest('tr').classList.add('table-active');

        audio.src = btn.dataset.src;
        titleEl.textContent = btn.dataset.title;
        artistEl.textContent = btn.dataset.artist;
        audio.play().catch(function () {});  // errors are handled by the 'error' event below
    }

    function togglePlay() {
        if (current === -1) {      // nothing loaded yet: start with the first song
            playSong(0);
        } else if (audio.paused) {
            audio.play();
        } else {
            audio.pause();
        }
    }

    // --- Buttons ---------------------------------------------------------
    queue.forEach(function (btn, index) {
        btn.addEventListener('click', function () {
            if (index === current) togglePlay();   // same song: play/pause
            else playSong(index);                  // different song: switch
        });
    });

    playBtn.addEventListener('click', togglePlay);
    nextBtn.addEventListener('click', function () { playSong(current + 1); });
    prevBtn.addEventListener('click', function () {
        // Like most players: more than 3 seconds in, "previous" restarts the song
        if (audio.currentTime > 3) audio.currentTime = 0;
        else playSong(current - 1);
    });

    // --- Sliders ---------------------------------------------------------
    seek.addEventListener('input', function () {    // while dragging: only move the time label
        dragging = true;
        timeCur.textContent = formatTime(seek.value);
    });
    seek.addEventListener('change', function () {   // on release: jump there
        audio.currentTime = seek.value;
        dragging = false;
    });
    volume.addEventListener('input', function () {
        audio.volume = volume.value;                 // 0 = silent, 1 = full
    });

    // --- Events from the <audio> tag -----------------------------------
    audio.addEventListener('loadedmetadata', function () {  // length is now known
        seek.max = Math.floor(audio.duration);
        timeTot.textContent = formatTime(audio.duration);
    });
    audio.addEventListener('timeupdate', function () {      // fires several times a second
        if (!dragging) seek.value = Math.floor(audio.currentTime);
        if (!dragging) timeCur.textContent = formatTime(audio.currentTime);
    });
    audio.addEventListener('play',  function () { setIcons(true); });
    audio.addEventListener('pause', function () { setIcons(false); });
    audio.addEventListener('ended', function () {           // song finished: next one
        setIcons(false);
        playSong(current + 1);
    });
    audio.addEventListener('error', function () {
        artistEl.textContent = 'Could not play this file.';
        setIcons(false);
    });
});