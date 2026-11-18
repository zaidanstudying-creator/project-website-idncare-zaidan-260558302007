@include('errors.layout', [
    'code' => '503',
    'title' => 'Layanan sedang tidak tersedia',
    'message' => 'IDN CARE sedang tidak dapat melayani permintaan. Coba kunjungi kembali setelah beberapa saat.',
    'primaryUrl' => url('/'),
    'primaryLabel' => 'Ke beranda',
    'secondaryUrl' => url('/login'),
    'secondaryLabel' => 'Masuk',
    'note' => 'Data dan informasi akun tetap dilindungi.',
])
