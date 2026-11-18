@include('errors.layout', [
    'code' => '401',
    'title' => 'Silakan masuk untuk melanjutkan',
    'message' => 'Sesi masuk tidak tersedia atau sudah berakhir. Masuk kembali untuk membuka halaman yang kamu tuju.',
    'primaryUrl' => url('/login'),
    'primaryLabel' => 'Masuk',
    'secondaryUrl' => url('/'),
    'secondaryLabel' => 'Ke beranda',
    'note' => 'Setelah masuk, buka kembali halaman yang kamu perlukan.',
])
