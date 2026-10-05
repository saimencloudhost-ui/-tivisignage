<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TV Kiosk - Saimen Digital Signage</title>
    <style>
        body, html {
            margin: 0; padding: 0;
            width: 100%; height: 100%;
            background-color: #000;
            overflow: hidden;
            display: flex; justify-content: center; align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* --- STYLE FORM LOGIN --- */
        #login-box {
            background: #1a1a1a;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            color: white;
            border: 2px solid #333;
            width: 450px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.7);
            z-index: 2000;
        }
        
        input, select, button {
            width: 100%;
            padding: 15px;
            margin: 12px 0;
            border-radius: 10px;
            border: 1px solid #444;
            background: #222;
            color: white;
            font-size: 1.1rem;
            box-sizing: border-box;
        }

        button {
            background: #28a745;
            border: none;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover { background: #218838; }

        /* --- STYLE SLIDER --- */
        #slider-container {
            position: relative;
            width: 100%;
            height: 100%;
            display: none;
            transition: transform 0.5s ease-in-out, width 0.5s, height 0.5s;
            transform-origin: center center;
        }

        .item {
            position: absolute; top: 0; left: 0;
            width: 100%; height: 100%;
            opacity: 0;
            transition: opacity 1.5s ease-in-out;
            object-fit: cover;
            object-position: center;
        }

        .item.active { opacity: 1; }

        /* --- OVERLAY INFO --- */
        #instruction {
            position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%);
            color: rgba(255,255,255,0.8); background: rgba(0,0,0,0.6);
            padding: 12px 25px; border-radius: 50px; 
            z-index: 1000; display: none;
            font-size: 1rem; border: 1px solid rgba(255,255,255,0.2);
        }

        #error-msg { color: #ff4d4d; margin-top: 10px; font-size: 0.9rem; }
        .item {
    display: none;
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.item.active {
    display: block;
    /* Optional: tambahkan animasi fade in */
    animation: fadeIn 0.5s; 
}
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    /* --- NOTIFIKASI TOAST --- */
    #notif-toast {
        position: fixed;
        top: 30px;
        right: 30px;
        background: rgba(40, 167, 69, 0.85); /* Hijau transparan */
        color: white;
        padding: 15px 30px;
        border-radius: 10px;
        font-size: 1.2rem;
        font-weight: bold;
        box-shadow: 0 5px 15px rgba(0,0,0,0.5);
        z-index: 3000;
        opacity: 0;
        visibility: hidden;
        transition: opacity 1s ease-in-out, visibility 1s ease-in-out;
        border: 1px solid rgba(255,255,255,0.2);
    }

    #notif-toast.show {
        opacity: 1;
        visibility: visible;
    }

    /* --- TOMBOL ROTASI --- */
    #rotate-btn {
        position: fixed;
        top: 20px;
        right: 20px;
        width: 45px;
        height: 45px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        color: rgba(255, 255, 255, 0.3);
        display: none;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        z-index: 4000;
        transition: all 0.3s;
        font-size: 20px;
    }
    #rotate-btn:hover { background: rgba(255, 255, 255, 0.2); color: white; }
    </style>
</head>
<body>
    <div id="notif-toast">✔️ Clear Cache Berhasil! Memuat ulang data...</div>
    <div id="rotate-btn" onclick="toggleRotation()" title="Putar Layar">&#8635;</div>

    <div id="login-box">
    <h2 style="margin-bottom: 20px;">Saimen TV System</h2>
    <input type="text" id="user-tv" placeholder="User / Nama Perangkat TV">

    <?php
    // 1. Panggil koneksi di paling atas sebelum menjalankan query
    // Sesuaikan path ini, jika file index ada di luar folder back, maka 'back/config.php' atau 'config.php'
    include 'back/koneksi.php'; 

    $daftar_cabang = []; // Inisialisasi array kosong agar tidak error jika database kosong

    try {
        // 2. Jalankan query untuk mengambil data cabang
        $query = "SELECT DISTINCT cabang FROM tbl_devicetv ORDER BY cabang ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $daftar_cabang = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // Tampilkan error jika query gagal
        echo "<script>console.log('Error Database: " . $e->getMessage() . "');</script>";
    }
    ?>

    <select id="cabang-tv" class="form-control" style="width: 100%; padding: 10px; margin-bottom: 15px; border-radius: 5px;">
        <option value="">-- Pilih Cabang --</option>
        <?php if (!empty($daftar_cabang)): ?>
            <?php foreach ($daftar_cabang as $row): ?>
                <option value="<?php echo htmlspecialchars($row['cabang']); ?>">
                    <?php echo ucwords(htmlspecialchars($row['cabang'])); ?>
                </option>
            <?php endforeach; ?>
        <?php else: ?>
            <option value="">Belum ada data cabang</option>
        <?php endif; ?>
    </select>

    <button onclick="prosesLogin()">MASUK & MULAI</button>
    <div id="error-msg"></div>
</div>

    <div id="instruction">Tekan OK / Klik Layar untuk Fullscreen & Suara</div>
    <div id="slider-container"></div>

<script>
        let tbl_kontentv = [];
        let currentIndex = 0;
        let slideInterval;
        let isActivated = false;
        let isUpdating = false; // Lock untuk mencegah update ganda
        let sessionCabang = localStorage.getItem("auth_cabang");
        let sessionUser = localStorage.getItem("auth_user");
        let sessionSlide = localStorage.getItem("auth_slide") !== "0"; // default to true
        let currentRotation = parseInt(localStorage.getItem("tv_rotation") || "0");

        const loginBox = document.getElementById('login-box');
        const slider = document.getElementById('slider-container');
        const instruction = document.getElementById('instruction');
        const errorLabel = document.getElementById('error-msg');

        // --- Variabel Player Daur Ulang ---
        let activeVideoPlayer = null;
        let activeImagePlayer = null;
        let currentBlobUrl = null;
        let messageContainer = null;

        // --- INIT INDEXEDDB ---
        const dbName = "SaimenTVCache";
        const storeName = "mediaFiles";
        let localDB;
        const requestDB = indexedDB.open(dbName, 1);
        requestDB.onupgradeneeded = function(e) {
            localDB = e.target.result;
            if (!localDB.objectStoreNames.contains(storeName)) {
                localDB.createObjectStore(storeName);
            }
        };
        requestDB.onsuccess = function(e) { localDB = e.target.result; };

        // Fungsi fetch dan cache media
        async function getCachedMedia(url) {
            return new Promise((resolve) => {
                if (!localDB) { resolve(url); return; }
                const tx = localDB.transaction([storeName], "readonly");
                const store = tx.objectStore(storeName);
                const req = store.get(url);
                req.onsuccess = async function(e) {
                    if (e.target.result) {
                        resolve(URL.createObjectURL(e.target.result));
                    } else {
                        try {
                            const separator = url.includes('?') ? '&' : '?';
                            const response = await fetch(url + separator + 'cb=' + new Date().getTime());
                            if (!response.ok) throw new Error("Network error");
                            const blob = await response.blob();
                            const writeTx = localDB.transaction([storeName], "readwrite");
                            writeTx.objectStore(storeName).put(blob, url);
                            resolve(URL.createObjectURL(blob));
                        } catch(err) {
                            console.error("Cache fetch error:", err);
                            resolve(url);
                        }
                    }
                };
                req.onerror = () => resolve(url);
            });
        }

        // Fungsi untuk menampilkan notifikasi toast
        function showNotif() {
            const notif = document.getElementById('notif-toast');
            if (notif) {
                notif.classList.add('show');
                setTimeout(() => {
                    notif.classList.remove('show');
                }, 5000);
            }
        }

        // --- POLLING PERINTAH ---
        function cekPerintahRemote() {
            if(!sessionUser || !sessionCabang) return;
            // Tambahkan &t= timestamp agar request GET tidak di-cache oleh LiteSpeed/Cloudflare di Hosting
            fetch(`cek_perintah.php?user=${encodeURIComponent(sessionUser)}&cabang=${encodeURIComponent(sessionCabang)}&t=${new Date().getTime()}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'ok') {
                    // Cek perubahan konfigurasi slide secara realtime
                    if (data.slide !== undefined) {
                        const newSlide = data.slide.toString() !== "0";
                        if (newSlide !== sessionSlide) {
                            sessionSlide = newSlide;
                            localStorage.setItem("auth_slide", newSlide ? "1" : "0");
                            console.log("Slide configuration updated dynamically:", newSlide);
                            clearTimeout(slideInterval);
                            startCycle();
                        }
                    }

                    if (data.perintah === 'CLEAR_CACHE') {
                        localStorage.removeItem("backup_playlist"); 
                        if (localDB) {
                            const tx = localDB.transaction([storeName], "readwrite");
                            const store = tx.objectStore(storeName);
                            store.clear();
                            tx.oncomplete = function() {
                                console.log("Cache IndexedDB berhasil dihapus. Memuat ulang data...");
                                showNotif();
                                isUpdating = false;
                                loadDatabase(false);
                            };
                        } else {
                            window.location.reload(true);
                        }
                    }
                }
            }).catch(e => console.log("Polling error:", e));
        }

        // --- 1. PROSES LOGIN ---
        async function prosesLogin() {
            const user = document.getElementById('user-tv').value;
            const cabang = document.getElementById('cabang-tv').value;
            if (!user || !cabang) {
                errorLabel.innerText = "Harap isi User dan Cabang!";
                return;
            }
            try {
                const response = await fetch('login_device.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `user=${encodeURIComponent(user)}&cabang=${encodeURIComponent(cabang)}`
                });
                const res = await response.json();
                if (res.status === "success") {
                    localStorage.setItem("auth_cabang", res.cabang);
                    localStorage.setItem("auth_user", user);
                    const slideVal = res.slide !== undefined ? res.slide.toString() : "1";
                    localStorage.setItem("auth_slide", slideVal);
                    sessionCabang = res.cabang;
                    sessionUser = user;
                    sessionSlide = slideVal !== "0";
                    activate(); 
                    jalankanAplikasi();
                } else {
                    errorLabel.innerText = res.message;
                }
            } catch (e) {
                errorLabel.innerText = "Gagal terhubung ke server!";
            }
        }

        // --- INISIALISASI ELEMEN RECYCLE ---
        function initPlayers() {
            slider.innerHTML = ""; // Bersihkan isi awal

            // Container pesan error / kosong
            messageContainer = document.createElement('div');
            messageContainer.style.cssText = "position:absolute; width:100%; height:100%; display:flex; justify-content:center; align-items:center; z-index:10;";
            slider.appendChild(messageContainer);

            // Player Gambar Tunggal
            activeImagePlayer = document.createElement('img');
            activeImagePlayer.className = 'item';
            
            // Player Video Tunggal
            activeVideoPlayer = document.createElement('video');
            activeVideoPlayer.className = 'item';
            activeVideoPlayer.muted = true;
            activeVideoPlayer.setAttribute('playsinline', 'true');
            activeVideoPlayer.setAttribute('webkit-playsinline', 'true'); 
            activeVideoPlayer.setAttribute('preload', 'auto'); // Auto aman karena hanya 1 video
            activeVideoPlayer.setAttribute('disablePictureInPicture', 'true');
            activeVideoPlayer.setAttribute('controlsList', 'nodownload');
            activeVideoPlayer.style.objectFit = 'cover';
            
            slider.appendChild(activeImagePlayer);
            slider.appendChild(activeVideoPlayer);
        }

        function showMessage(msg) {
            if(messageContainer) messageContainer.innerHTML = `<h2 style='color:white; text-align:center;'>${msg}</h2>`;
            if(activeVideoPlayer) activeVideoPlayer.classList.remove('active');
            if(activeImagePlayer) activeImagePlayer.classList.remove('active');
        }

        function hideMessage() {
            if(messageContainer) messageContainer.innerHTML = "";
        }

        function jalankanAplikasi() {
            loginBox.style.display = 'none';
            slider.style.display = 'block';
            document.getElementById('rotate-btn').style.display = 'flex';
            
            if(!isActivated) {
                instruction.style.display = 'block';
            }
            initPlayers(); // Siapkan 2 tag untuk di daur ulang
            applyRotation();
            loadDatabase();
            
            setInterval(cekPerintahRemote, 10000);
            setInterval(() => loadDatabase(true), 30000); // Sinkronisasi background data setiap 30 detik
        }

        // --- FITUR ROTASI ---
        function applyRotation() {
            const container = document.getElementById('slider-container');
            container.style.transform = `rotate(${currentRotation}deg)`;
            
            // Jika rotasi 90 atau 270 derajat, tukar dimensi width/height agar tetap fullscreen
            if (currentRotation === 90 || currentRotation === 270) {
                container.style.width = '100vh';
                container.style.height = '100vw';
            } else {
                container.style.width = '100%';
                container.style.height = '100%';
            }
        }

        function toggleRotation() {
            currentRotation = (currentRotation + 90) % 360;
            localStorage.setItem("tv_rotation", currentRotation);
            applyRotation();
        }

        function parseLocalDateTime(str) {
            if (!str) return null;
            const parts = str.split(/[- :T]/);
            if (parts.length < 3) return null;
            const y = parseInt(parts[0], 10);
            const m = parseInt(parts[1], 10) - 1;
            const d = parseInt(parts[2], 10);
            const hr = parts.length > 3 ? parseInt(parts[3], 10) : 0;
            const min = parts.length > 4 ? parseInt(parts[4], 10) : 0;
            const sec = parts.length > 5 ? parseInt(parts[5], 10) : 0;
            return new Date(y, m, d, hr, min, sec);
        }

        function filterKontenTerjadwal(rawData) {
            const now = new Date();
            return rawData.filter(d => {
                const isAktif = d.nama.toLowerCase() === 'aktif';
                const daftarCabang = d.cabang.toLowerCase().split(',');
                const isCabangCocok = daftarCabang.includes(sessionCabang.toLowerCase());
                const isAllCabang = d.cabang.toLowerCase() === 'all';
                
                // Cek Penjadwalan Otomatis (Zona Indonesia sesuai jam perangkat TV Kiosk)
                let isScheduled = true;
                if (d.tanggal_mulai && d.tanggal_mulai !== "0000-00-00 00:00:00" && d.tanggal_mulai !== "null") {
                    const start = parseLocalDateTime(d.tanggal_mulai);
                    if (start && now < start) isScheduled = false;
                }
                if (d.tanggal_selesai && d.tanggal_selesai !== "0000-00-00 00:00:00" && d.tanggal_selesai !== "null") {
                    const end = parseLocalDateTime(d.tanggal_selesai);
                    if (end && now > end) isScheduled = false;
                }

                return isAktif && (isCabangCocok || isAllCabang) && isScheduled;
            });
        }

        async function loadDatabase(isBackground = false) {
            if (isUpdating) return;
            isUpdating = true;

            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 7000); 
                const response = await fetch('data.php?t=' + new Date().getTime(), { signal: controller.signal });
                clearTimeout(timeoutId);

                if (!response.ok) throw new Error("Gagal load API (Kemungkinan Offline)");
                const rawData = await response.json();

                // Simpan data mentah untuk filter dinamis offline
                localStorage.setItem("raw_backup_playlist", JSON.stringify(rawData));

                const new_tbl_kontentv = filterKontenTerjadwal(rawData);

                const oldDataString = JSON.stringify(tbl_kontentv);
                const newDataString = JSON.stringify(new_tbl_kontentv);

                tbl_kontentv = new_tbl_kontentv;
                localStorage.setItem("backup_playlist", newDataString);

                if (tbl_kontentv.length > 0) {
                    hideMessage();
                    if (oldDataString !== newDataString || !isBackground) {
                        clearTimeout(slideInterval);
                        currentIndex = 0;
                        startCycle();
                    }
                } else {
                    showMessage("Tidak ada konten aktif.");
                }
            } catch (err) {
                console.error("Gagal memuat data konten dari server:", err);
                
                const rawBackup = localStorage.getItem("raw_backup_playlist");
                let new_tbl_kontentv = [];
                if (rawBackup) {
                    const rawData = JSON.parse(rawBackup);
                    new_tbl_kontentv = filterKontenTerjadwal(rawData);
                } else {
                    const backup = localStorage.getItem("backup_playlist");
                    if (backup) {
                        new_tbl_kontentv = JSON.parse(backup);
                    }
                }

                const oldDataString = JSON.stringify(tbl_kontentv);
                const newDataString = JSON.stringify(new_tbl_kontentv);
                tbl_kontentv = new_tbl_kontentv;

                if (tbl_kontentv.length > 0) {
                    hideMessage();
                    if (oldDataString !== newDataString || !isBackground) {
                        clearTimeout(slideInterval);
                        currentIndex = 0;
                        startCycle();
                    }
                } else {
                    showMessage("Tidak ada koneksi dan tidak ada data cache.");
                }
            } finally {
                isUpdating = false;
            }
        }

        async function startCycle() {
            if (tbl_kontentv.length === 0) return;
            
            // Pengaturan apakah transisi slide aktif
            const isSlideEnabled = sessionSlide && tbl_kontentv.length > 1;
            
            const data = tbl_kontentv[currentIndex];
            clearTimeout(slideInterval);

            // Bersihkan player sebelumnya dari layar
            if (activeVideoPlayer) {
                activeVideoPlayer.pause();
                activeVideoPlayer.classList.remove('active');
            }
            if (activeImagePlayer) {
                activeImagePlayer.classList.remove('active');
            }

            try {
                // Tentukan path lengkap media di dalam folder KONTEN
                const mediaUrl = data.src.startsWith('KONTEN/') ? data.src : 'KONTEN/' + data.src;
                // Ambil URL media dari IndexedDB (cache) atau Download
                const cachedUrl = await getCachedMedia(mediaUrl);

                // CEGAH MEMORY LEAK: Hapus Blob URL slide sebelumnya jika berbeda
                if (currentBlobUrl && currentBlobUrl.startsWith('blob:') && currentBlobUrl !== cachedUrl) {
                    URL.revokeObjectURL(currentBlobUrl);
                }
                currentBlobUrl = cachedUrl;

                if (data.ket === "video") {
                    // Daur ulang tag video
                    activeVideoPlayer.src = cachedUrl;
                    activeVideoPlayer.muted = !isActivated;
                    activeVideoPlayer.classList.add('active'); 
                    
                    // Set native loop jika fitur slide dinonaktifkan atau konten hanya 1
                    activeVideoPlayer.loop = !isSlideEnabled;
                    
                    activeVideoPlayer.onerror = function() {
                        console.log("Media error: ", this.src);
                        if (isSlideEnabled) gantiSlide();
                    };
                    
                    activeVideoPlayer.onended = isSlideEnabled ? () => gantiSlide() : null;
                    
                    let playPromise = activeVideoPlayer.play();
                    if (playPromise !== undefined) {
                        playPromise.catch(err => {
                            console.log("Video Play Error/Blocked: ", err);
                            if (isSlideEnabled) {
                                slideInterval = setTimeout(gantiSlide, 3000); // Jika error, skip slide ini
                            }
                        });
                    }
                } else {
                    // Daur ulang tag gambar
                    activeImagePlayer.src = cachedUrl;
                    activeImagePlayer.classList.add('active');
                    
                    activeImagePlayer.onerror = function() {
                        console.log("Image error: ", this.src);
                        if (isSlideEnabled) gantiSlide();
                    };
                    
                    // Hanya set timeout transisi jika fitur slide aktif
                    if (isSlideEnabled) {
                        slideInterval = setTimeout(gantiSlide, 8000); 
                    }
                }
            } catch(err) {
                console.error("Error loading media for slide:", err);
                if (isSlideEnabled) {
                    slideInterval = setTimeout(gantiSlide, 3000);
                }
            }
        }

        function gantiSlide() {
            if (tbl_kontentv.length === 0) return;

            if (currentIndex === tbl_kontentv.length - 1) {
                // Satu putaran selesai
                currentIndex = 0;
                startCycle();
                // Cek data baru secara transparan di background
                loadDatabase(true); 
            } else {
                currentIndex++;
                startCycle();
            }
        }

        // --- 4. PERBAIKAN FUNGSI AKTIVASI (FULLSCREEN & AUDIO) ---
        function activate() {
            const doc = document.documentElement;
            
            if (doc.requestFullscreen) doc.requestFullscreen().catch(()=>{});
            else if (doc.webkitRequestFullscreen) doc.webkitRequestFullscreen();
            else if (doc.mozRequestFullScreen) doc.mozRequestFullScreen();
            else if (doc.msRequestFullscreen) doc.msRequestFullscreen();

            isActivated = true;
            instruction.style.display = 'none';

            // Jika video sedang aktif, mainkan suaranya
            if (activeVideoPlayer && activeVideoPlayer.classList.contains('active')) {
                activeVideoPlayer.muted = false;
                activeVideoPlayer.play().catch(() => {});
            }
        }

        window.onload = () => {
            if (sessionCabang) jalankanAplikasi();
        };

        document.addEventListener('click', activate);
        document.addEventListener('keydown', function(e) {
            activate();
        });
    </script>
</body>
</html>