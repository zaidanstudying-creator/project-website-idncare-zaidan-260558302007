@include('errors.layout', [
    'code' => '419',
    'title' => 'Sesi kamu telah berakhir',
    'message' => 'Untuk menjaga keamanan akun, formulir ini tidak dikirim. Masuk kembali lalu mulai ulang tindakanmu.',
    'primaryUrl' => url('/login'),
    'primaryLabel' => 'Masuk kembali',
    'secondaryUrl' => url('/'),
    'secondaryLabel' => 'Ke beranda',
    'note' => 'Data formulir yang belum terkirim mungkin perlu diisi kembali.',
])
