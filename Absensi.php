<!DOCTYPE html> <!-- Mendefinisikan dokumen HTML5 -->
<html lang="id"> <!-- Bahasa dokumen: Indonesia -->
<head>
    <meta charset="UTF-8"> <!-- Set karakter encoding -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Supaya responsif -->
    <title>Sistem Absensi</title> <!-- Judul tab browser -->
    <link rel="stylesheet" href="style.css"> <!-- Menghubungkan file CSS -->
</head>
<body>
    <div class="container"> <!-- Container utama -->

        <div class="header"> <!-- Bagian header atas -->
            <h1>📋 Sistem Absensi Siswa & Siswi XI RPL 1</h1> <!-- Judul aplikasi -->
            <div class="datetime" id="datetime"></div> <!-- Tempat tampil tanggal & jam -->
        </div>

        <?php
        // Set timezone Indonesia (WITA - Bali)
        date_default_timezone_set('Asia/Makassar');
        
        // File JSON tempat menyimpan data absensi
        $dataFile = 'absensi_data.json';
        
        // Fungsi membaca data dari file JSON
        function bacaData($file) {
            if (file_exists($file)) { // Cek apakah file ada
                $json = file_get_contents($file); // Baca isi file JSON
                return json_decode($json, true) ?: []; // Ubah JSON → array
            }
            return []; // Jika file tidak ada, kembalikan array kosong
        }
        
        // Fungsi menyimpan data kembali ke file JSON
        function simpanData($file, $data) {
            $json = json_encode($data, JSON_PRETTY_PRINT); // Convert array → JSON
            return file_put_contents($file, $json); // Simpan ke file
        }
        
        // Baca data absensi yang ada
        $absensi = bacaData($dataFile);
        
        $message = '';     // Variabel pesan notifikasi
        $messageType = ''; // Jenis pesan: success / error
        
        // Jika form disubmit
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // Jika menekan tombol RESET
            if (isset($_POST['reset'])) {
                $absensi = [];                       // Hapus semua data
                simpanData($dataFile, $absensi);     // Simpan file kosong
                $message = "Semua data absensi telah dihapus!"; // Pesan sukses
                $messageType = 'success';

            // Jika menekan tombol hapus pada satu data
            } elseif (isset($_POST['hapus'])) {
                $index = (int)$_POST['hapus']; // Ambil index data yang mau dihapus
                
                if (isset($absensi[$index])) { // Cek apakah index valid
                    $namaHapus = $absensi[$index]['nama']; // Ambil nama untuk pesan
                    unset($absensi[$index]);               // Hapus 1 data
                    $absensi = array_values($absensi);     // Susun ulang index array
                    simpanData($dataFile, $absensi);       // Simpan perubahan
                    $message = "Data absensi {$namaHapus} berhasil dihapus!"; // Pesan
                    $messageType = 'success';
                }

            // Jika mengisi absensi masuk/keluar
            } else {
                $nama = htmlspecialchars(trim($_POST['nama'])); // Ambil nama
                $nik = htmlspecialchars(trim($_POST['nik']));   // Ambil NIK
                $jenis = $_POST['jenis'];                       // Masuk / Keluar
                $waktu = date('Y-m-d H:i:s');                   // Waktu saat ini
                
                if (!empty($nama) && !empty($nik)) { // Validasi input tidak kosong
                    // Tambahkan data baru
                    $absensi[] = [
                        'nama' => $nama,
                        'nik' => $nik,
                        'jenis' => $jenis,
                        'waktu' => $waktu,
                        'tanggal' => date('Y-m-d') // Simpan tanggal saja
                    ];
                    
                    simpanData($dataFile, $absensi); // Simpan perubahan
                    
                    $message = "Absensi {$jenis} berhasil dicatat untuk {$nama}!"; // Pesan
                    $messageType = 'success';
                } else {
                    // Jika nama/NIK kosong
                    $message = "Mohon lengkapi semua data!";
                    $messageType = 'error';
                }
            }
        }
        
        // Menentukan tanggal filter, default hari ini
        $filterTanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

        // Filter data berdasarkan tanggal
        $absensiFiltered = array_filter($absensi, function($item) use ($filterTanggal) {
            return $item['tanggal'] === $filterTanggal;
        });
        ?>

        <?php if ($message): ?> <!-- Menampilkan notifikasi jika ada pesan -->
        <div class="alert alert-<?= $messageType ?>">
            <?= $message ?> <!-- Isi pesan -->
        </div>
        <?php endif; ?>

        <div class="card"> <!-- Card form absensi -->
            <h2 style="margin-bottom: 20px; color: #333;">Form Absensi</h2>
            <form method="POST" action=""> <!-- Form kirim data -->
                
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" placeholder="Masukkan nama lengkap" required>
                </div>

                <div class="form-group">
                    <label>NIK SISWA & SISWI</label>
                    <input type="text" name="nik" placeholder="Masukkan NIK" required>
                </div>

                <div class="btn-group"> <!-- Tombol Masuk & Keluar -->
                    <button type="submit" name="jenis" value="Masuk" class="btn-masuk">
                        ✓ Absen Masuk
                    </button>
                    <button type="submit" name="jenis" value="Keluar" class="btn-keluar">
                        ✗ Absen Keluar
                    </button>
                </div>
            </form>
        </div>

        <div class="card"> <!-- Card riwayat absensi -->
            <h2 style="margin-bottom: 20px; color: #333;">Riwayat Absensi</h2>

            <!-- Bagian filter tanggal -->
            <div class="filter-section">
                <form method="GET" style="display: flex; gap: 10px; width: 100%;">
                    <div class="form-group" style="flex: 1;">
                        <label>Filter Tanggal</label>
                        <input type="date" name="tanggal" value="<?= $filterTanggal ?>" onchange="this.form.submit()">
                    </div>
                </form>

                <!-- Tombol reset semua data -->
                <form method="POST" style="min-width: 150px;">
                    <button type="submit" name="reset" class="btn-reset" onclick="return confirm('Yakin ingin menghapus semua data absensi?')">
                        🗑️ Reset Data
                    </button>
                </form>
            </div>
            
            <?php if (empty($absensiFiltered)): ?> <!-- Jika tidak ada data -->
                <p style="text-align: center; color: #999; padding: 20px;">
                    Belum ada data absensi untuk tanggal ini
                </p>

            <?php else: ?> <!-- Jika ada data -->
                <div style="overflow-x: auto;">
                    <table> <!-- Tabel absensi -->
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>NIK</th>
                                <th>Status</th>
                                <th>Waktu</th>
                                <th>Aksi</th> <!-- KOLOM AKSI DITAMBAHKAN -->
                            </tr>
                        </thead>

                        <tbody>
                            <?php 
                            $no = 1; // Nomor urut
                            $reversedData = array_reverse($absensi, true); // Ditampilkan terbaru dulu
                            
                            foreach ($reversedData as $key => $data):
                                if ($data['tanggal'] !== $filterTanggal) continue; // Skip jika tidak cocok tanggal
                            ?>
                            <tr>
                                <td><?= $no++ ?></td> <!-- Nomor -->
                                <td><?= $data['nama'] ?></td> <!-- Nama -->
                                <td><?= $data['nik'] ?></td> <!-- NIK -->
                                <td>
                                    <span class="status-badge status-<?= strtolower($data['jenis']) ?>">
                                        <?= $data['jenis'] ?> <!-- Masuk / Keluar -->
                                    </span>
                                </td>
                                <td><?= date('H:i:s', strtotime($data['waktu'])) ?></td> <!-- Jam -->
                                
                                <!-- Tombol hapus pada 1 data -->
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <button type="submit" name="hapus" 
                                                value="<?= $key ?>" 
                                                class="btn-hapus" 
                                                onclick="return confirm('Yakin ingin menghapus data absensi <?= $data['nama'] ?>?')">
                                            ❌
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <p style="margin-top: 15px; color: #666; font-size: 14px;">
                    Total: <?= count($absensiFiltered) ?> absensi |
                    Total Keseluruhan: <?= count($absensi) ?> absensi
                </p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Fungsi untuk update jam & tanggal secara realtime
        function updateDateTime() {
            const now = new Date(); // Waktu saat ini

            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                timeZone: 'Asia/Makassar' // Timezone Indonesia WITA (Bali)
            };

            // Tampilkan ke elemen dengan id 'datetime'
            document.getElementById('datetime').textContent = 
                now.toLocaleDateString('id-ID', options);
        }

        updateDateTime(); // Jalankan saat halaman dibuka
        setInterval(updateDateTime, 1000); // Update setiap 1 detik
    </script>
</body>
</html>