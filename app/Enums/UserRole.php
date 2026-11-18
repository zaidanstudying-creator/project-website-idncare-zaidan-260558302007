<?php

namespace App\Enums;

enum UserRole: string
{
    case MAHASISWA = 'mahasiswa';
    case ADMIN_SARPRAS = 'admin_sarpras';
    case ADMIN_KEMAHASISWAAN = 'admin_kemahasiswaan';
    case SUPER_ADMIN = 'super_admin';
}
