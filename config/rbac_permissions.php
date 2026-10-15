<?php

return [
    'modules' => [
        'users' => [
            'name' => 'Manajemen Pengguna (User Management)',
            'icon' => 'fa-solid fa-users',
            'permissions' => [
                'users.view' => [
                    'label' => 'Melihat Daftar Pengguna',
                    'description' => 'Melihat daftar akun pengguna, rincian profil, dan status penempatan pos.',
                ],
                'users.create' => [
                    'label' => 'Menambah Pengguna Baru',
                    'description' => 'Membuat akun pengguna baru dan menetapkan kredensial awal.',
                ],
                'users.edit' => [
                    'label' => 'Mengubah Data Pengguna',
                    'description' => 'Mengubah nama, email, username, dan penempatan pos gerbang pengguna.',
                ],
                'users.change_password' => [
                    'label' => 'Mengubah Password Pengguna',
                    'description' => 'Mereset atau memperbarui kata sandi pengguna dari panel admin.',
                ],
                'users.assign_role' => [
                    'label' => 'Menetapkan Role Pengguna',
                    'description' => 'Menetapkan atau mengubah wewenang role Spatie kepada pengguna.',
                ],
                'users.delete' => [
                    'label' => 'Menghapus Pengguna',
                    'description' => 'Menghapus akun pengguna dari sistem (dengan proteksi akun utama).',
                ],
            ],
        ],
        'roles' => [
            'name' => 'Manajemen Role & RBAC (Role & Permissions)',
            'icon' => 'fa-solid fa-shield-halved',
            'permissions' => [
                'roles.view' => [
                    'label' => 'Melihat Matriks Role & Hak Akses',
                    'description' => 'Melihat daftar role dan pemetaan centang izin wewenang sistem.',
                ],
                'roles.create' => [
                    'label' => 'Membuat Role Kustom Baru',
                    'description' => 'Menambahkan role tingkatan wewenang baru ke dalam sistem.',
                ],
                'roles.edit_permissions' => [
                    'label' => 'Mengubah Hak Akses Role',
                    'description' => 'Memperbarui centang matriks izin/permissions untuk setiap role.',
                ],
                'roles.delete' => [
                    'label' => 'Menghapus Role Kustom',
                    'description' => 'Menghapus role kustom yang sudah tidak digunakan (role inti terlindungi).',
                ],
            ],
        ],
        'hse' => [
            'name' => 'Modul HSE (Karyawan & SIMPER)',
            'icon' => 'fa-solid fa-id-card',
            'permissions' => [
                'hse.employees.view' => [
                    'label' => 'Melihat Data Karyawan & SIMPER',
                    'description' => 'Melihat daftar master karyawan, status operator/driver, dan masa berlaku SIMPER.',
                ],
                'hse.employees.create' => [
                    'label' => 'Menambah Data Karyawan / Driver',
                    'description' => 'Menambahkan karyawan baru ke master data HSE.',
                ],
                'hse.employees.edit' => [
                    'label' => 'Mengubah Data Karyawan / SIMPER',
                    'description' => 'Memperbarui status driver, unit kerja, divisi, dan perpanjangan tanggal SIMPER.',
                ],
            ],
        ],
        'qma' => [
            'name' => 'Modul QMA (Kendaraan & Log Aktivitas)',
            'icon' => 'fa-solid fa-car',
            'permissions' => [
                'qma.vehicles.view' => [
                    'label' => 'Melihat Master Kendaraan',
                    'description' => 'Melihat master data kendaraan ringan (LV) dan unit support.',
                ],
                'qma.vehicles.create' => [
                    'label' => 'Menambah Unit Kendaraan',
                    'description' => 'Menambahkan unit kendaraan baru dan dokumen kendaraan.',
                ],
                'qma.vehicles.edit' => [
                    'label' => 'Mengubah Data Kendaraan',
                    'description' => 'Memperbarui informasi status, HM/KM, dokumen STNK/KIR/SILO kendaraan.',
                ],
                'qma.vehicles.delete' => [
                    'label' => 'Menghapus Data Kendaraan',
                    'description' => 'Menghapus unit kendaraan (terlindungi jika memiliki riwayat aktivitas).',
                ],
                'qma.logs.view' => [
                    'label' => 'Melihat Log Aktivitas Kendaraan',
                    'description' => 'Melihat riwayat 10 kolom pergerakan kendaraan masuk/keluar proyek.',
                ],
                'qma.summary.view' => [
                    'label' => 'Melihat Ringkasan Aktivitas & Statistik',
                    'description' => 'Melihat summary dan grafik agregat penggunaan kendaraan.',
                ],
            ],
        ],
        'pg' => [
            'name' => 'Modul Private Guard (Satpam & Verifikasi Gerbang)',
            'icon' => 'fa-solid fa-user-shield',
            'permissions' => [
                'pg.guards.view' => [
                    'label' => 'Melihat Data Satpam',
                    'description' => 'Melihat daftar personil satpam, regu/tim jaga, dan penempatan pos gerbang.',
                ],
                'pg.guards.create' => [
                    'label' => 'Menambah Personil Satpam Baru',
                    'description' => 'Mendaftarkan satpam baru dan otomatis membuatkan akun login lapangan.',
                ],
                'pg.guards.edit' => [
                    'label' => 'Mengubah Data Satpam',
                    'description' => 'Mengubah regu, nama, nomor badge, dan pos penugasan satpam.',
                ],
                'pg.guards.toggle' => [
                    'label' => 'Mengaktifkan / Menonaktifkan Satpam',
                    'description' => 'Mengubah status aktif personil satpam untuk bertugas di pos.',
                ],
                'pg.guards.delete' => [
                    'label' => 'Menghapus Data Satpam',
                    'description' => 'Menghapus personil satpam dan akun login terkait.',
                ],
                'pg.logs.view' => [
                    'label' => 'Melihat Log Verifikasi Pos Gerbang',
                    'description' => 'Melihat riwayat audit pemeriksaan yang telah disetujui / ditolak oleh satpam.',
                ],
            ],
        ],
        'gate' => [
            'name' => 'Pos Gerbang Lapangan (Gate Officer Verification)',
            'icon' => 'fa-solid fa-shield-halved',
            'permissions' => [
                'gate.inspect' => [
                    'label' => 'Membuka Layar Antrean Pos Gerbang',
                    'description' => 'Melihat antrean live kendaraan yang masuk/keluar di pos gerbang.',
                ],
                'gate.approve' => [
                    'label' => 'Menyetujui Verifikasi Kendaraan (Approve)',
                    'description' => 'Menyetujui perlintasan kendaraan dan memperbarui odometer master.',
                ],
                'gate.reject' => [
                    'label' => 'Menolak Verifikasi Kendaraan (Reject)',
                    'description' => 'Menolak perlintasan kendaraan dengan memberikan catatan alasan.',
                ],
            ],
        ],
    ],
];
