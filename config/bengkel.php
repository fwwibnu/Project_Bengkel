<?php

/*
| Identitas bengkel untuk sidebar, nota, dan laporan cetak.
| Ubah lewat .env tanpa menyentuh kode.
*/

return [
    'nama' => env('BENGKEL_NAMA', 'Maju Jaya Motor'),
    'nama_lengkap' => env('BENGKEL_NAMA_LENGKAP', 'Bengkel Maju Jaya Motor'),
    'alamat' => env('BENGKEL_ALAMAT', 'Jl. Raya Lenteng Agung No. 45, Jakarta Selatan'),
    'telepon' => env('BENGKEL_TELEPON', '(021) 7890-1234'),
];
