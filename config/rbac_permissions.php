<?php

return [
    'modules' => [
        'users' => [
            'name' => 'Manajemen Akun Pengguna',
            'icon' => 'fa-solid fa-users',
            'permissions' => [
                'users.view' => [
                    'label' => 'Melihat Daftar Pengguna',
                    'description' => 'Melihat daftar akun pengguna, rincian profil, dan status penempatan pos.',
                ],
                'users.create' => [
                    'label' => 'Menambah Pengguna Baru',
                    'description' => 'Membuat akun login baru untuk karyawan atau petugas lapangan.',
                ],
                'users.edit' => [
                    'label' => 'Mengubah Data Pengguna',
                    'description' => 'Mengubah nama, email, username, dan penempatan pos gerbang pengguna.',
                ],
                'users.change_password' => [
                    'label' => 'Mengubah Password Pengguna',
                    'description' => 'Mengatur ulang kata sandi (password) akun pengguna.',
                ],
                'users.assign_role' => [
                    'label' => 'Menentukan Peran Pengguna',
                    'description' => 'Menentukan jabatan atau peran tugas pengguna di sistem (misal: Admin HSE, Petugas Satpam, Karyawan).',
                ],
                'users.delete' => [
                    'label' => 'Menghapus Pengguna',
                    'description' => 'Menghapus akun pengguna dari sistem (akun administrator utama terlindungi).',
                ],
            ],
        ],
        'roles' => [
            'name' => 'Pengaturan Peran & Hak Akses',
            'icon' => 'fa-solid fa-shield-halved',
            'permissions' => [
                'roles.view' => [
                    'label' => 'Melihat Daftar Peran & Hak Akses',
                    'description' => 'Melihat daftar jabatan dan rincian menu yang boleh diakses.',
                ],
                'roles.create' => [
                    'label' => 'Membuat Role Baru',
                    'description' => 'Membuat jabatan atau peran baru di luar peran bawaan sistem.',
                ],
                'roles.edit_permissions' => [
                    'label' => 'Mengatur Hak Akses Peran',
                    'description' => 'Menentukan menu dan tindakan apa saja yang boleh dibuka oleh suatu peran.',
                ],
                'roles.delete' => [
                    'label' => 'Menghapus Role Tambahan',
                    'description' => 'Menghapus peran tambahan yang sudah tidak digunakan (peran utama sistem terlindungi).',
                ],
            ],
        ],
        'hse' => [
            'name' => 'Modul K3 / HSE (Karyawan & SIMPER)',
            'icon' => 'fa-solid fa-id-card',
            'permissions' => [
                'hse.employees.view' => [
                    'label' => 'Melihat Data Karyawan & SIMPER',
                    'description' => 'Melihat daftar karyawan, izin mengemudi (SIMPER), dan masa berlakunya.',
                ],
                'hse.employees.create' => [
                    'label' => 'Mendaftarkan Karyawan Baru',
                    'description' => 'Mendaftarkan data karyawan atau pengemudi baru ke dalam sistem.',
                ],
                'hse.employees.edit' => [
                    'label' => 'Memperbarui Data Karyawan & SIMPER',
                    'description' => 'Mengubah data karyawan, status pengemudi, atau memperpanjang tanggal masa berlaku SIMPER.',
                ],
            ],
        ],
        'qma' => [
            'name' => 'Modul Operasional Armada & Kendaraan',
            'icon' => 'fa-solid fa-car',
            'permissions' => [
                'qma.vehicles.view' => [
                    'label' => 'Melihat Daftar Kendaraan & Manajemen Unit',
                    'description' => 'Melihat armada LV, Heavy Equipment, monitoring posisi unit, dan transfer unit alat berat.',
                ],
                'qma.vehicles.create' => [
                    'label' => 'Mendaftarkan Kendaraan & Transfer Unit',
                    'description' => 'Mendaftarkan unit kendaraan baru atau membuat form transfer pemindahan unit.',
                ],
                'qma.vehicles.edit' => [
                    'label' => 'Memperbarui Kendaraan & Posisi Unit',
                    'description' => 'Mengubah data unit, dokumen STNK/KIR/SILO, pembaruan lokasi unit, atau transfer unit.',
                ],
                'qma.vehicles.delete' => [
                    'label' => 'Menghapus Kendaraan & Transfer',
                    'description' => 'Menghapus data kendaraan atau riwayat transfer unit dari sistem.',
                ],
                'qma.logs.view' => [
                    'label' => 'Melihat Catatan Perjalanan & Timesheet Heavy Equipment',
                    'description' => 'Melihat riwayat mobilisasi perjalanan (Timesheet LV) dan daftar Timesheet Heavy Equipment.',
                ],
                'qma.logs.edit_odometer' => [
                    'label' => 'Koreksi Odometer & Kelola Timesheet Heavy Equipment',
                    'description' => 'Mengoreksi angka kilometer perjalanan LV serta menambah, mengubah, dan menghapus Timesheet Heavy Equipment.',
                ],
                'qma.logs.delete' => [
                    'label' => 'Menghapus Catatan Perjalanan (Timesheet LV)',
                    'description' => 'Menghapus riwayat data perjalanan mobilisasi kendaraan operasional dari sistem.',
                ],
                'qma.summary.view' => [
                    'label' => 'Melihat Rekapitulasi & Laporan Armada',
                    'description' => 'Melihat total kilometer, jumlah perjalanan, dan ringkasan penggunaan armada per periode.',
                ],
                'qma.p2h.view' => [
                    'label' => 'Melihat Riwayat Pemeriksaan Harian (P2H)',
                    'description' => 'Melihat riwayat lembar cek fisik alat berat sebelum mulai bekerja.',
                ],
                'qma.p2h.approve' => [
                    'label' => 'Persetujuan Kelayakan Alat (Approval P2H)',
                    'description' => 'Menyetujui atau menolak izin beroperasi alat berat berdasarkan hasil checklist operator.',
                ],
                'qma.p2h.templates' => [
                    'label' => 'Mengatur Formulir Checklist Pemeriksaan',
                    'description' => 'Menambah, mengubah, dan menyusun butir pertanyaan pada formulir checklist pemeriksaan harian.',
                ],
            ],
        ],
        'pg' => [
            'name' => 'Modul Keamanan & Pos Satpam',
            'icon' => 'fa-solid fa-user-shield',
            'permissions' => [
                'pg.guards.view' => [
                    'label' => 'Melihat Daftar Petugas Satpam',
                    'description' => 'Melihat daftar personil satpam, pembagian regu piket, dan penempatan pos jaga.',
                ],
                'pg.guards.create' => [
                    'label' => 'Mendaftarkan Petugas Satpam Baru',
                    'description' => 'Menambahkan personil satpam baru dan otomatis membuatkan akun login pos jaga.',
                ],
                'pg.guards.edit' => [
                    'label' => 'Memperbarui Data Petugas Satpam',
                    'description' => 'Mengubah nama, nomor badge, regu jaga, atau memindahkan pos penugasan satpam.',
                ],
                'pg.guards.toggle' => [
                    'label' => 'Mengubah Status Tugas Satpam',
                    'description' => 'Mengaktifkan atau menonaktifkan status tugas personil satpam di lapangan.',
                ],
                'pg.guards.delete' => [
                    'label' => 'Menghapus Petugas Satpam',
                    'description' => 'Menghapus data personil satpam dan mencabut akun login posnya.',
                ],
                'pg.logs.view' => [
                    'label' => 'Melihat Riwayat Keluar-Masuk Gerbang',
                    'description' => 'Melihat catatan kendaraan yang diperiksa, disetujui, atau ditolak oleh satpam di pos gerbang.',
                ],
                'pg.logs.delete' => [
                    'label' => 'Menghapus Riwayat Verifikasi Gerbang',
                    'description' => 'Menghapus catatan log verifikasi perlintasan kendaraan pos gerbang dari sistem.',
                ],
            ],
        ],
        'gate' => [
            'name' => 'Pemeriksaan di Pos Gerbang',
            'icon' => 'fa-solid fa-shield-halved',
            'permissions' => [
                'gate.inspect' => [
                    'label' => 'Membuka Layar Antrean Pos Gerbang',
                    'description' => 'Melihat antrean langsung kendaraan yang tiba di pos gerbang untuk diperiksa.',
                ],
                'gate.approve' => [
                    'label' => 'Mengizinkan Kendaraan Melintas (Setujui Gerbang)',
                    'description' => 'Menyetujui kendaraan melintas dan mencatat angka kilometer terkini.',
                ],
                'gate.reject' => [
                    'label' => 'Menolak Kendaraan Melintas (Tolak Gerbang)',
                    'description' => 'Menolak kendaraan melintas gerbang dan mencatat alasan penolakannya.',
                ],
            ],
        ],
        'employee' => [
            'name' => 'Menu Mandiri Karyawan & Pengemudi',
            'icon' => 'fa-solid fa-id-badge',
            'permissions' => [
                'employee.dashboard' => [
                    'label' => 'Membuka Halaman Utama Karyawan',
                    'description' => 'Melihat ringkasan data diri karyawan, masa berlaku SIMPER, dan menu kerja harian.',
                ],
                'employee.p2h' => [
                    'label' => 'Mengisi Form P2H Heavy Equipment',
                    'description' => 'Mengakses katalog Heavy Equipment dan menginput checklist pra-harian P2H sebelum beroperasi.',
                ],
                'employee.timesheet' => [
                    'label' => 'Mencatat Perjalanan Kendaraan (Timesheet / Scan QR)',
                    'description' => 'Mengisi formulir keberangkatan dan kepulangan mobil operasional melalui scan stiker QR.',
                ],
            ],
        ],
    ],
];
