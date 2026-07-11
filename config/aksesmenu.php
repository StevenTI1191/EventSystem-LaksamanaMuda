<?php

/*
|--------------------------------------------------------------------------
| Akses Menu Sidebar per Role (visibilitas tombol, bukan CRUD)
|--------------------------------------------------------------------------
| Sumber kebenaran tunggal untuk menu sidebar tiap role internal.
|   - locked  : selalu tampil, tidak bisa dimatikan (mis. Dashboard)
|   - default : true  -> tercentang secara default (base akses role)
|               false -> opsional, default mati (checkbox di Manajemen Pegawai)
| Kolom `akses_menu` (JSON) di tabel pegawais menyimpan daftar key yang aktif.
| NULL pada kolom itu = pakai kumpulan default di bawah.
*/

return [

    'EventMarketing' => [
        ['key' => 'dashboard',   'label' => 'Dashboard',      'locked' => true],
        ['key' => 'kalender',    'label' => 'Kalender',       'default' => true],
        ['key' => 'events',      'label' => 'Events',         'default' => true],
        ['key' => 'planning',    'label' => 'Planning Event', 'default' => true],
        ['key' => 'client',      'label' => 'Client',         'default' => true],
        ['key' => 'appointment', 'label' => 'Appointment',    'default' => true],
        ['key' => 'transaksi',   'label' => 'Transaksi',      'default' => false],
    ],

    'Finance' => [
        ['key' => 'dashboard', 'label' => 'Dashboard',        'locked' => true],
        ['key' => 'transaksi', 'label' => 'Transaksi',        'default' => true],
        ['key' => 'bukti',     'label' => 'Bukti Pembayaran', 'default' => true],
        ['key' => 'client',    'label' => 'Client',           'default' => true],
        ['key' => 'laporan',   'label' => 'Laporan',          'default' => true],
        ['key' => 'event',     'label' => 'Event',            'default' => false],
        ['key' => 'kalender',  'label' => 'Kalender',         'default' => false],
    ],

];
