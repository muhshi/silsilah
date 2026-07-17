<x-mail::message>
# Anda Diundang Berkolaborasi!

Seseorang telah mengundang Anda untuk berkolaborasi mengedit silsilah keluarga **{{ $tree->name }}**. 

Sebagai Kolaborator (Editor), Anda dapat menambahkan anggota keluarga baru, memperbarui informasi profil, dan membantu melengkapi silsilah ini.

<x-mail::button :url="$acceptUrl">
Terima Undangan
</x-mail::button>

*Jika Anda belum memiliki akun, Anda akan diminta untuk masuk menggunakan Google secara aman.*

Terima kasih,<br>
Tim {{ config('app.name') }}
</x-mail::message>
