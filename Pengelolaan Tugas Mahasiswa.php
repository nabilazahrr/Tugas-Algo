<?php
session_start();

/*
 * Aplikasi Pengelolaan Tugas Mahasiswa
 * Bahasa: PHP dan HTML
 * Algoritma: Sorting, Searching, Stack, Queue, Profiling
 */

if (!isset($_SESSION['tasks'])) {
    $_SESSION['tasks'] = [
        [
            'id' => 1,
            'judul' => 'Tugas Struktur Data',
            'matkul' => 'Struktur Data',
            'deadline' => '2026-10-20',
            'status' => 'Belum selesai'
        ],
        [
            'id' => 2,
            'judul' => 'Tugas Algoritma',
            'matkul' => 'Algoritma',
            'deadline' => '2026-10-22',
            'status' => 'Selesai'
        ]
    ];

    $_SESSION['next_id'] = 3;
}

$_SESSION['stack'] ??= [];
$_SESSION['queue'] ??= [];

$pesan = '';

function aman($teks) {
    return htmlspecialchars(
        (string) $teks,
        ENT_QUOTES,
        'UTF-8'
    );
}

/* =========================
   BUBBLE SORT
   ========================= */

function bubbleSort($data, $kolom) {
    $n = count($data);

    for ($i = 0; $i < $n - 1; $i++) {
        $berubah = false;

        for ($j = 0; $j < $n - $i - 1; $j++) {
            if (
                strcmp(
                    strtolower($data[$j][$kolom]),
                    strtolower($data[$j + 1][$kolom])
                ) > 0
            ) {
                $sementara = $data[$j];
                $data[$j] = $data[$j + 1];
                $data[$j + 1] = $sementara;

                $berubah = true;
            }
        }

        // Optimasi: berhenti jika sudah terurut.
        if (!$berubah) {
            break;
        }
    }

    return $data;
}

/* =========================
   SELECTION SORT
   ========================= */

function selectionSort($data, $kolom) {
    $n = count($data);

    for ($i = 0; $i < $n - 1; $i++) {
        $min = $i;

        for ($j = $i + 1; $j < $n; $j++) {
            if (
                strcmp(
                    strtolower($data[$j][$kolom]),
                    strtolower($data[$min][$kolom])
                ) < 0
            ) {
                $min = $j;
            }
        }

        if ($min != $i) {
            $sementara = $data[$i];
            $data[$i] = $data[$min];
            $data[$min] = $sementara;
        }
    }

    return $data;
}

/* =========================
   LINEAR SEARCH
   ========================= */

function linearSearch($data, $kata) {
    foreach ($data as $tugas) {
        if (
            stripos($tugas['judul'], $kata) !== false
        ) {
            return $tugas;
        }
    }

    return null;
}

/* =========================
   BINARY SEARCH
   Judul harus sama persis.
   ========================= */

function binarySearch($data, $kata) {
    $data = bubbleSort($data, 'judul');

    $kiri = 0;
    $kanan = count($data) - 1;

    while ($kiri <= $kanan) {
        $tengah = intdiv($kiri + $kanan, 2);

        $hasil = strcasecmp(
            $data[$tengah]['judul'],
            $kata
        );

        if ($hasil == 0) {
            return $data[$tengah];
        }

        if ($hasil < 0) {
            $kiri = $tengah + 1;
        } else {
            $kanan = $tengah - 1;
        }
    }

    return null;
}

/* =========================
   PROSES FORM
   ========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    // Tambah tugas
    if ($aksi === 'tambah') {
        $judul = trim($_POST['judul'] ?? '');
        $matkul = trim($_POST['matkul'] ?? '');
        $deadline = $_POST['deadline'] ?? '';

        if ($judul !== '' && $matkul !== '') {
            $_SESSION['tasks'][] = [
                'id' => $_SESSION['next_id']++,
                'judul' => $judul,
                'matkul' => $matkul,
                'deadline' => $deadline,
                'status' => 'Belum selesai'
            ];

            $pesan = 'Tugas berhasil ditambahkan.';
        } else {
            $pesan = 'Judul dan mata kuliah wajib diisi.';
        }
    }

    // Ubah status dan simpan riwayat ke stack
    elseif ($aksi === 'status') {
        $id = (int) ($_POST['id'] ?? 0);

        foreach ($_SESSION['tasks'] as &$tugas) {
            if ($tugas['id'] === $id) {
                $_SESSION['stack'][] = [
                    'id' => $tugas['id'],
                    'status' => $tugas['status']
                ];

                $tugas['status'] =
                    $tugas['status'] === 'Selesai'
                    ? 'Belum selesai'
                    : 'Selesai';

                break;
            }
        }

        unset($tugas);
        $pesan = 'Status tugas berhasil diubah.';
    }

    // Hapus tugas
    elseif ($aksi === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);

        $_SESSION['tasks'] = array_values(
            array_filter(
                $_SESSION['tasks'],
                fn($t) => $t['id'] !== $id
            )
        );

        $pesan = 'Tugas berhasil dihapus.';
    }

    // Urutkan tugas
    elseif ($aksi === 'urutkan') {
        $_SESSION['kolom'] = $_POST['kolom'] ?? 'deadline';
        $_SESSION['algoritma'] = $_POST['algoritma'] ?? 'bubble';
    }

    // Cari tugas
    elseif ($aksi === 'cari') {
        $_SESSION['kata_cari'] = trim($_POST['kata_cari'] ?? '');
        $_SESSION['metode_cari'] = $_POST['metode_cari'] ?? 'linear';
    }

    elseif ($aksi === 'reset_cari') {
        $_SESSION['kata_cari'] = '';
    }

    // STACK: membatalkan status terakhir
    elseif ($aksi === 'undo') {
        $riwayat = array_pop($_SESSION['stack']);

        if ($riwayat !== null) {
            foreach ($_SESSION['tasks'] as &$tugas) {
                if ($tugas['id'] === $riwayat['id']) {
                    $tugas['status'] = $riwayat['status'];
                    break;
                }
            }

            unset($tugas);
            $pesan = 'Status terakhir berhasil dikembalikan.';
        } else {
            $pesan = 'Stack masih kosong.';
        }
    }

    // QUEUE: menambah antrean
    elseif ($aksi === 'enqueue') {
        $item = trim($_POST['item'] ?? '');

        if ($item !== '') {
            $_SESSION['queue'][] = $item;
            $pesan = 'Item masuk ke antrean.';
        }
    }

    // QUEUE: memproses antrean pertama
    elseif ($aksi === 'dequeue') {
        $item = array_shift($_SESSION['queue']);

        $pesan = $item !== null
            ? 'Antrean diproses: ' . $item
            : 'Antrean masih kosong.';
    }
}

/* =========================
   SORTING DAN PROFILING
   ========================= */

$tugas = $_SESSION['tasks'];

$kolom = $_SESSION['kolom'] ?? 'deadline';
$algoritma = $_SESSION['algoritma'] ?? 'bubble';

if (!in_array($kolom, ['judul', 'matkul', 'deadline'], true)) {
    $kolom = 'deadline';
}

if (!in_array($algoritma, ['bubble', 'selection'], true)) {
    $algoritma = 'bubble';
}

$mulai = microtime(true);

if ($algoritma === 'selection') {
    $tugas = selectionSort($tugas, $kolom);
} else {
    $tugas = bubbleSort($tugas, $kolom);
}

$waktu_sort = (microtime(true) - $mulai) * 1000;

/* =========================
   SEARCHING DAN PROFILING
   ========================= */

$kata = $_SESSION['kata_cari'] ?? '';
$metode = $_SESSION['metode_cari'] ?? 'linear';

$hasil_cari = null;
$waktu_cari = 0;

if ($kata !== '') {
    $mulai = microtime(true);

    if ($metode === 'binary') {
        $hasil_cari = binarySearch($tugas, $kata);
    } else {
        $hasil_cari = linearSearch($tugas, $kata);
    }

    $waktu_cari = (microtime(true) - $mulai) * 1000;
}

$total = count($tugas);
$selesai = count(
    array_filter(
        $tugas,
        fn($t) => $t['status'] === 'Selesai'
    )
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>TaskMate</title>
</head>
<body>

<h1>Aplikasi Pengelolaan Tugas Mahasigma</h1>

<hr>

<?php if ($pesan !== ''): ?>
    <p><strong><?= aman($pesan) ?></strong></p>
<?php endif; ?>

<h2>Dashboard</h2>

<p>Total tugas: <?= $total ?></p>
<p>Tugas selesai: <?= $selesai ?></p>
<p>Tugas belum selesai: <?= $total - $selesai ?></p>

<hr>

<h2>Tambah Tugas</h2>

<form method="post">
    <input type="hidden" name="aksi" value="tambah">

    <p>
        Judul tugas:
        <input type="text" name="judul" required>
    </p>

    <p>
        Mata kuliah:
        <input type="text" name="matkul" required>
    </p>

    <p>
        Tenggat:
        <input type="date" name="deadline">
    </p>

    <button type="submit">Tambah Tugas</button>
</form>

<hr>

<h2>Sorting (Pengurutan)</h2>

<form method="post">
    <input type="hidden" name="aksi" value="urutkan">

    <select name="kolom">
        <option value="deadline">Tenggat</option>
        <option value="judul">Judul</option>
        <option value="matkul">Mata kuliah</option>
    </select>

    <select name="algoritma">
        <option value="bubble">Bubble Sort</option>
        <option value="selection">Selection Sort</option>
    </select>

    <button type="submit">Urutkan</button>
</form>

<p>Waktu sorting: <?= number_format($waktu_sort, 6) ?> ms</p>

<hr>

<h2>Searching (Pencarian)</h2>

<form method="post">
    <input type="hidden" name="aksi" value="cari">

    <input
        type="text"
        name="kata_cari"
        placeholder="Judul tugas"
        value="<?= aman($kata) ?>"
        required
    >

    <select name="metode_cari">
        <option value="linear">Linear Search</option>
        <option value="binary">Binary Search</option>
    </select>

    <button type="submit">Cari</button>
</form>

<?php if ($kata !== ''): ?>
    <?php if ($hasil_cari): ?>
        <p>
            Ditemukan:
            <?= aman($hasil_cari['judul']) ?>
            — <?= aman($hasil_cari['matkul']) ?>
        </p>
    <?php else: ?>
        <p>Tugas tidak ditemukan.</p>
    <?php endif; ?>

    <p>Waktu pencarian: <?= number_format($waktu_cari, 6) ?> ms</p>

    <form method="post">
        <button name="aksi" value="reset_cari">
            Bersihkan pencarian
        </button>
    </form>
<?php endif; ?>

<hr>

<h2>Daftar Tugas</h2>

<table border="1" cellpadding="6" cellspacing="0">
    <tr>
        <th>Judul</th>
        <th>Mata Kuliah</th>
        <th>Tenggat</th>
        <th>Status</th>
        <th>Aksi</th>
    </tr>

    <?php foreach ($tugas as $item): ?>
    <tr>
        <td><?= aman($item['judul']) ?></td>
        <td><?= aman($item['matkul']) ?></td>
        <td><?= aman($item['deadline'] ?: '-') ?></td>
        <td><?= aman($item['status']) ?></td>
        <td>
            <form method="post">
                <input
                    type="hidden"
                    name="id"
                    value="<?= $item['id'] ?>"
                >

                <button name="aksi" value="status">
                    Ubah Status
                </button>

                <button
                    name="aksi"
                    value="hapus"
                    onclick="return confirm('Hapus tugas ini?')"
                >
                    Hapus
                </button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<ol>
    <?php foreach ($_SESSION['queue'] as $item): ?>
        <li><?= aman($item) ?></li>
    <?php endforeach; ?>
</ol>

<hr>

</body>
</html>
