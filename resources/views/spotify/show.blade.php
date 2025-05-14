<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Şu Anda Çalan Şarkı</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: sans-serif; padding: 20px; }
        img { max-width: 300px; border-radius: 10px; }
        .info { margin-top: 20px; }
        .progress-container {
            width: 100%; height: 10px; background: #eee; border-radius: 5px; margin-top: 10px;
        }
        .progress-bar {
            height: 100%; width: 0%; background: #1DB954; border-radius: 5px;
        }
        .progress-text {
            margin-top: 10px;
            font-size: 14px;
        }
        .playlist-url {
            margin-top: 15px;
            font-size: 16px;
            color: #1DB954;
        }
    </style>
</head>
<body>

<h1>🎵 Şu Anda Çalan Şarkı</h1>

<div id="song">
    <p>Yükleniyor...</p>
</div>

<div class="progress-container">
    <div id="progress-bar" class="progress-bar"></div>
</div>

<div id="progress-text" class="progress-text">
    <p>00:00 / 00:00</p>
</div>

<div id="playlist-url" class="playlist-url">
    <!-- Playlist URL buraya basılacak -->
</div>

<script>
    let progressMs = 0;
    let durationMs = 1;
    let playing = false;

    // Dakika ve saniyeye dönüştür
    function formatTime(ms) {
        const minutes = Math.floor(ms / 60000);
        const seconds = Math.floor((ms % 60000) / 1000);
        return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }

    // UI'da ilerleme çubuğunu ve zaman metnini güncelle
    setInterval(() => {
        if (!playing) return;
        progressMs += 100;
        if (progressMs > durationMs) progressMs = durationMs;

        const percent = (progressMs / durationMs) * 100;
        document.getElementById("progress-bar").style.width = percent + "%";

        const currentTime = formatTime(progressMs);
        const totalTime = formatTime(durationMs);
        document.getElementById("progress-text").innerHTML = `<p>${currentTime} / ${totalTime}</p>`;
    }, 100);

    async function fetchCurrentTrack() {
        try {
            const response = await fetch("/spotify/playing");
            const data = await response.json();

            const container = document.getElementById("song");
            const playlistUrlContainer = document.getElementById("playlist-url");

            if (data.track) {
                // Global değişkenleri güncelle
                progressMs = data.progress_ms;
                durationMs = data.track.duration_ms;
                playing = data.is_playing;

                container.innerHTML = `
                    <img src="${data.track.album.images[0].url}" alt="Kapak">
                    <div class="info">
                        <h2>${data.track.name}</h2>
                        <p><strong>Sanatçı:</strong> ${data.track.artists.map(a => a.name).join(", ")}</p>
                        <p><strong>Albüm:</strong> ${data.track.album.name}</p>
                        <p><a href="${data.track.external_urls.spotify}" target="_blank">Spotify'da Aç</a></p>
                    </div>
                `;

                // Playlist URL'si varsa, göster
                if (data.playlist && data.playlist.external_urls && data.playlist.external_urls.spotify) {
                    playlistUrlContainer.innerHTML = `<p>🎧 Playlist: <a href="${data.playlist.external_urls.spotify}" target="_blank">Buradan dinleyin</a></p>`;
                } else {
                    playlistUrlContainer.innerHTML = "";  // Playlist URL'si yoksa temizle
                }
            } else {
                container.innerHTML = "<p>Şu anda şarkı çalmıyor.</p>";
                playing = false;
                playlistUrlContainer.innerHTML = "";  // Şarkı çalmıyorsa playlist URL'sini temizle
            }
        } catch (error) {
            console.error("Hata:", error);
        }
    }

    // Sayfa yüklendiğinde ve her 5 saniyede bir çağır
    fetchCurrentTrack();
    setInterval(fetchCurrentTrack, 3000);
</script>

</body>
</html>
