@include('errors.layout', [
    'code' => '500',
    'title' => 'Terjadi kesalahan pada server',
    'message' => 'Maaf, terjadi masalah di sisi kami. Kembali ke halaman yang aman dan coba lagi beberapa saat lagi.',
    'primaryUrl' => url('/'),
    'primaryLabel' => 'Ke beranda',
    'secondaryUrl' => url('/login'),
    'secondaryLabel' => 'Masuk',
    'note' => 'Jika masalah berulang, hubungi admin kampus.',
])
