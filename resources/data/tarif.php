<?php

// Tarif per format layar, dipisah hari biasa dan akhir pekan.
// Akhir pekan dihitung Jumat sampai Minggu, seperti kebanyakan bioskop di Indonesia.
// Di database nanti angkanya ada di kolom price tabel showtimes.

return [
    'Regular 2D' => ['biasa' => 45000, 'akhirPekan' => 55000],
    'Regular 3D' => ['biasa' => 55000, 'akhirPekan' => 65000],
    'IMAX' => ['biasa' => 85000, 'akhirPekan' => 100000],
    'Premiere 2D' => ['biasa' => 100000, 'akhirPekan' => 125000],
];
