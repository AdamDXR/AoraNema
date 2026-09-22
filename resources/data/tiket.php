<?php

// Tiket contoh untuk halaman Tiket Saya. Hapus file ini begitu pesanan asli bisa disimpan.
//
// Bentuknya sengaja satu pesanan berisi beberapa kursi dengan satu kode, karena begitulah
// seharusnya. Tabel bookings sekarang masih satu baris untuk satu kursi, jadi bentuk ini
// belum bisa diambil dari database apa adanya.
//
// 'hari' dihitung dari hari ini: positif berarti akan datang, negatif berarti sudah lewat.
// Dibuat relatif supaya contohnya tidak basi seiring waktu.

return [
    ['slug' => 'kabut-di-ujung-jalan', 'layar' => 'Regular 2D',  'jam' => '20:30', 'hari' => 1,   'kursi' => ['D8', 'D10'],       'metode' => 'qris',    'dibatalkan' => false],
    ['slug' => 'lorong-sunyi',         'layar' => 'IMAX',        'jam' => '15:00', 'hari' => 3,   'kursi' => ['F5'],              'metode' => 'ewallet', 'dibatalkan' => false],
    ['slug' => 'senja-terakhir',       'layar' => 'Premiere 2D', 'jam' => '19:40', 'hari' => -4,  'kursi' => ['B3', 'B4'],        'metode' => 'va',      'dibatalkan' => false],
    ['slug' => 'sembilan-detik',       'layar' => 'Regular 3D',  'jam' => '13:20', 'hari' => -11, 'kursi' => ['E6', 'E7', 'E8'],  'metode' => 'qris',    'dibatalkan' => true],
    ['slug' => 'anak-rantau',          'layar' => 'Regular 2D',  'jam' => '12:30', 'hari' => -19, 'kursi' => ['C1'],              'metode' => 'qris',    'dibatalkan' => false],
];
