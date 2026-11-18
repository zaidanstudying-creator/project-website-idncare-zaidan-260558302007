@include('errors.layout', [
    'code' => '403',
    'title' => 'Kamu tidak memiliki izin',
    'message' => 'Halaman ini hanya tersedia untuk akun dengan kewenangan yang sesuai.',
    'primaryUrl' => url('/dashboard'),
    'primaryLabel' => 'Ke dashboard',
    'secondaryUrl' => url('/'),
    'secondaryLabel' => 'Ke beranda',
    'note' => 'Jika menurutmu akses ini seharusnya tersedia, hubungi admin kampus.',
])
