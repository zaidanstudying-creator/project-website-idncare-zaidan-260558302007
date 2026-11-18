@include('errors.layout', [
    'code' => '429',
    'title' => 'Terlalu banyak permintaan',
    'message' => 'Permintaanmu dibatasi sementara. Tunggu sebentar sebelum melanjutkan.',
    'primaryUrl' => url('/'),
    'primaryLabel' => 'Ke beranda',
    'secondaryUrl' => url('/login'),
    'secondaryLabel' => 'Masuk',
    'note' => 'Batas keamanan aplikasi tetap aktif untuk melindungi akun dan layanan.',
])
