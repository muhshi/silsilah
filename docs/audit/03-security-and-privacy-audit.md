# Phase 2B — Security & Privacy Audit Report

**Komponen Diperiksa:** Livewire SFCs, `routes/web.php`, `ImageProxyController.php`, Policy Authorization  
**Severity Scale:** Critical (P0) > High (P1) > Medium (P2) > Low (P3)

---

## 1. Temuan Keamanan Utama

### SEC-01 [CRITICAL - P0]: Cross-Tree IDOR (Insecure Direct Object Reference) pada Mutasi Anggota dan Pernikahan
- **Lokasi File:** [`resources/views/components/⚡member-manager.blade.php#L642-L644`](file:///Users/saiful/development/silsilah/resources/views/components/%E2%9A%A1member-manager.blade.php#L642-L644), [`#L770-L777`](file:///Users/saiful/development/silsilah/resources/views/components/%E2%9A%A1member-manager.blade.php#L770-L777), [`#L789-L835`](file:///Users/saiful/development/silsilah/resources/views/components/%E2%9A%A1member-manager.blade.php#L789-L835)
- **Vulnerability Description:**
  Otorisasi Gate `Gate::authorize('editMembers', $tree)` hanya memverifikasi bahwa user yang sedang login memiliki hak edit pada `$this->treeId`. Namun, saat mengambil record anggota (`Member`) atau pernikahan (`Marriage`) yang akan diedit/dihapus, query dilakukan secara global tanpa scoping ke `$tree->id`:
  
  **Bukti Kode 1 (Edit/Hijack Member):**
  ```php
  // resources/views/components/⚡member-manager.blade.php
  if ($this->memberId) {
      $member = Member::findOrFail($this->memberId); // TIDAK ADA pengecekan family_tree_id!
      $member->update($data); // data berisi 'family_tree_id' => $this->treeId
  ```
  
  **Bukti Kode 2 (Delete Member):**
  ```php
  public function deleteMember()
  {
      \Illuminate\Support\Facades\Gate::authorize('editMembers', FamilyTree::findOrFail($this->treeId));
      if ($this->memberId) {
          $member = Member::find($this->memberId); // Global query!
          if ($member) {
              $member->delete(); // Dapat menghapus anggota milik pohon orang lain!
          }
      }
  }
  ```

  **Bukti Kode 3 (Mutasi / Hapus Pernikahan):**
  ```php
  public function deleteMarriage($marriageId)
  {
      $tree = FamilyTree::findOrFail($this->treeId);
      \Illuminate\Support\Facades\Gate::authorize('editMembers', $tree);

      $marriage = Marriage::findOrFail($marriageId); // Global query!
      $marriage->delete(); // Dapat menghapus pernikahan pohon orang lain!
  }
  ```

- **Dampak Serangan:**
  Pengguna terautentikasi yang memiliki pohon sendiri dapat mengirimkan payload Livewire dengan `memberId` atau `marriageId` milik pohon keluarga pengguna lain, lalu mengubah nama/foto, memindahkan anggota tersebut ke pohonnya sendiri, atau menghapus anggota/pernikahan tersebut secara permanen.
- **Rekomendasi Perbaikan:**
  Wajib melakukan query scoped ke pohon yang sedang diotorisasi:
  ```php
  $member = $tree->members()->findOrFail($this->memberId);
  ```
  Dan untuk pernikahan:
  ```php
  $marriage = Marriage::whereHas('husband', fn($q) => $q->where('family_tree_id', $tree->id))
      ->findOrFail($marriageId);
  ```

---

### SEC-02 [HIGH - P1]: Rate Limiting pada Endpoint Sensitif (`api/image-proxy` & `orders/checkout`)
- **Lokasi File:** [`routes/web.php#L22`](file:///Users/saiful/development/silsilah/routes/web.php#L22), [`routes/web.php#L45`](file:///Users/saiful/development/silsilah/routes/web.php#L45)
- **Vulnerability Description:**
  Meskipun `ImageProxyController` sudah memiliki validasi SSRF yang baik (memblokir private IP dan scheme berbahaya), rute `GET /api/image-proxy` dan `POST /orders/checkout` belum dibungkus oleh middleware `throttle` yang ketat.
- **Dampak:**
  Potensi penyerang memanfaatkan server sebagai *proxy relay* publik dalam jumlah masif (DoS terhadap server atau outbound bandwidth saturation) serta spamming order checkout tanpa throttling.
- **Rekomendasi Perbaikan:**
  Tambahkan middleware `throttle:60,1` untuk image proxy dan `throttle:10,1` untuk checkout di `routes/web.php`.

---

### SEC-03 [MEDIUM - P2]: Living Person Privacy & PII Exposure pada Tampilan Publik
- **Lokasi File:** [`resources/views/tree/public.blade.php`](file:///Users/saiful/development/silsilah/resources/views/tree/public.blade.php), [`resources/views/components/tree-node.blade.php`](file:///Users/saiful/development/silsilah/resources/views/components/tree-node.blade.php)
- **Vulnerability Description:**
  Standar privasi silsilah internasional (Genealogical Privacy Standard / GDPR compliance) mengharuskan data pribadi orang yang masih hidup (*living individuals*) seperti tanggal lahir lengkap, alamat, nomor telepon, dan WhatsApp disamarkan (*masked* / disembunyikan) dari pengunjung publik umum, kecuali login sebagai anggota pohon yang disetujui.
- **Dampak:**
  Jika pohon silsilah diatur sebagai publik (`is_public = true`), seluruh informasi tanggal lahir dan identitas generasi muda dapat diakses oleh publik luas tanpa filter sensor otomatis.
- **Rekomendasi Perbaikan:**
  Tambahkan opsi privasi pohon: *"Samarkan data orang yang masih hidup untuk publik"* (sembunyikan tanggal lahir lengkap, hanya tampilkan tahun atau tanda "Hidup").
