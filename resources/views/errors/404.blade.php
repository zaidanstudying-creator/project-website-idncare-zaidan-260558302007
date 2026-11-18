@include('errors.layout', [
    'code' => '404',
    'title' => 'Halaman tidak ditemukan',
    'message' => 'Halaman yang kamu cari tidak ditemukan. Periksa alamatnya atau kembali ke beranda.',
    'primaryUrl' => url('/'),
    'primaryLabel' => 'Kembali ke beranda',
    'secondaryUrl' => url('/login'),
    'secondaryLabel' => 'Masuk',
    'note' => 'Jika tautan ini berasal dari IDN CARE, hubungi admin kampus.',
])
