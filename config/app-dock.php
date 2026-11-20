<?php

// Dock bawah (4 item: 3 menu utama + Menu) untuk role Extras & Korlap di mobile (<=768px).
// Satu sumber data: dock + sisanya (menu_extra, saat ini kosong karena sidebar tiap role cuma 3 item)
// dirender generik lewat @foreach di layouts/app.blade.php, bukan markup hardcode per role.

return [
    'extras' => [
        'account_route' => 'extras.profile.edit',
        'items' => [
            ['icon' => 'ti-home', 'label' => 'Beranda', 'route' => 'extras.dashboard', 'active' => ['extras.dashboard']],
            ['icon' => 'ti-microphone', 'label' => 'Lowongan', 'route' => 'extras.projects.index', 'active' => ['extras.projects.*']],
            ['icon' => 'ti-user-circle', 'label' => 'Profil', 'route' => 'extras.profile.edit', 'active' => ['extras.profile.*']],
        ],
        'menu_extra' => [],
    ],
    'korlap' => [
        'account_route' => 'ubah-password',
        'items' => [
            ['icon' => 'ti-layout-dashboard', 'label' => 'Dashboard', 'route' => 'admin.dashboard', 'active' => ['admin.dashboard']],
            ['icon' => 'ti-clipboard-check', 'label' => 'Absensi', 'route' => 'admin.attendance.index', 'active' => ['admin.attendance.*']],
            ['icon' => 'ti-history', 'label' => 'Honor', 'route' => 'admin.work-history', 'active' => ['admin.work-history', 'admin.payrolls.*']],
        ],
        'menu_extra' => [],
    ],
];
