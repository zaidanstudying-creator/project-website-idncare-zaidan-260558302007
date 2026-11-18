@include('errors.layout', [
    'code' => '422',
    'title' => 'Data belum dapat diproses',
    'message' => 'Berkas atau informasi yang dikirim belum memenuhi ketentuan. Periksa kembali lalu mulai ulang tindakanmu.',
    'primaryUrl' => request()->is('admin/users/import*') ? url('/admin/users/import') : url('/dashboard'),
    'primaryLabel' => request()->is('admin/users/import*') ? 'Kembali ke impor' : 'Ke dashboard',
    'secondaryUrl' => url('/'),
    'secondaryLabel' => 'Ke beranda',
    'note' => 'Untuk impor akun, buka kembali halaman impor dan unggah berkas yang sudah diperbaiki.',
])
