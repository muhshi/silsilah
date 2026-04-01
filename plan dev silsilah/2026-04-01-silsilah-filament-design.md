# Design Spec: Silsilah Keluarga — Livewire Edition
*(Sebelumnya: Filament 5 Edition)*

**Tanggal:** 1 April 2026
**Status:** Final
**Stack:** Laravel 13 + Livewire 4 + Alpine.js + Tailwind CSS v4 + panzoom.js

---

## 1. Ringkasan & Perubahan Arsitektur

Berdasarkan evaluasi terhadap UI referensi (`list_silsilah.html`), diputuskan bahwa **Filament 5 terlalu berlebihan (overkill)** untuk use-case aplikasi ini. Aplikasi Silsilah Keluarga memiliki alur UX yang sangat spesifik (berpusat pada 1 Canvas Tree utama) yang tidak cocok dikurung dalam layout "Admin Panel" standar.

**Fitur inti (Livewire-based):**
- Auth sederhana (Laravel Breeze)
- Dashboard `Silsilah Keluargaku` (Card grid layout -> ref: `list_silsilah.html`)
- Visualisasi tree horizontal (CSS tree + panzoom -> ref: `horizontal_view.html`) dan vertical (ref: `vertical_view.html`)
- Table view opsional 
- CRUD member modal (Livewire)
- Avatar: upload custom atau pilih dari 18 preset
- Public link sharing (`/s/{slug}`)
- Export PNG (html2canvas)

## 2. Database Relational Structure

Konsep database **TETAP SAMA** dengan desain sebelumnya (Hybrid: Direct Parent + Separate Marriage).

**Tabel:**
1. `users`: Bawaan Laravel
2. `family_trees`: id, name, description, is_public, public_slug
3. `family_tree_user` (Pivot): family_tree_id, user_id, role (owner, editor, viewer)
4. `members`: id, family_tree_id, father_id, mother_id, first_name, last_name, gender, birth_date, death_date, photo_path, avatar_preset, bio, profession, birth_place, death_place, company, interests, address, phone(WA), facebook, instagram, member_notes, birth_order. *(Hanya 3 sosmed)*
5. `marriages`: id, family_tree_id, husband_id, wife_id, marriage_date, divorce_date, is_current. *(Mendukung mantan)*

## 3. UI/UX Flow & Routes

Seluruh aplikasi dibangun sebagai Single-Page Application feel menggunakan **Livewire Wire:navigate**.

| Route | View Component | Keterangan |
|---|---|---|
| `GET /` | `welcome.blade.php` | Landing page publik |
| `GET /login` | `Breeze Auth` | Halaman Login/Register |
| `GET /dashboard` | `App\Livewire\Dashboard` | List silsilah (cards) + Modal Create Silsilah |
| `GET /tree/{id}` | `App\Livewire\FamilyTreeView` | Kanvas pohon keluarga + Livewire CRUD Member Modal |
| `GET /s/{slug}` | `App\Http\Controllers\PublicTreeController` | Read-only public view |

### 3.1 Layout Dashboard (`/dashboard`)
Mengikuti desain referensi `list_silsilah.html`:
- Top navbar dengan logo, profile dropdown, "Buat Silsilah".
- Main area: Grid kotak/cards berisi judul silsilah, waktu dibuat, jumlah anggota.

### 3.2 Layout Tree View (`/tree/{id}`)
Canvas full-width:
- Top bar: [⬅ Kembali], Judul Pohon, [Toggle Horizontal/Vertical/Table], [📷 Download], [Share].
- Area Canvas: Draggable (panzoom.js), berisi list CSS Tree.
- Modal Action: Alpine.js + Livewire (Add/Edit/Hapus).

## 4. Arsitektur Frontend (Livewire + Alpine)

```
┌───────────────────────────────────────────────────────────┐
│  Livewire Component: \App\Livewire\FamilyTreeView         │
│                                                           │
│  State:                                                   │
│  - $tree (FamilyTree)                                     │
│  - $rootMembers, $marriages                               │
│  - $memberForm[] (state form modal)                       │
│  - $showMemberModal (boolean)                             │
│                                                           │
│  Methods:                                                 │
│  - loadTree(): refresh queries                            │
│  - saveMember(), deleteMember($id)                        │
│                                                           │
│  ┌─────────────────────────────────────────────────────┐  │
│  │  Blade Template                                     │  │
│  │                                                     │  │
│  │  <div x-data="treeCanvas" id="panzoom-container">   │  │
│  │     @foreach($rootMembers...)                       │  │
│  │        <x-tree-node />  (Recursive blade component) │  │
│  │     @endforeach                                     │  │
│  │  </div>                                             │  │
│  │                                                     │  │
│  │  <x-modal wire:model="showMemberModal">             │  │
│  │     // Tab: Pribadi, Kontak, Biografi               │  │
│  │  </x-modal>                                         │  │
│  └─────────────────────────────────────────────────────┘  │
└───────────────────────────────────────────────────────────┘
```

**Kelebihan over Filament:**
- DOM 100% kita kontrol. Tidak ada bentrok dengan layout panel dan padding bawaan Filament.
- Asset size jauh lebih kecil karena tidak me-load seluruh component library Filament.
- Performa navigasi murni Livewire 4.

## 5. Security & Authorization

Tanpa Filament Shield, kita akan gunakan **Laravel Policies** dan **Gates** murni.

```php
// MemberPolicy.php
public function update(User $user, Member $member): bool
{
    $pivot = $member->familyTree->users()->where('user_id', $user->id)->first();
    return $pivot && in_array($pivot->pivot->role, ['owner', 'editor']);
}
```

## 6. Development Phasing Baru

1. **Phase 1: Foundation (Laravel Breeze + DB)**
   - Setup Laravel 13, Tailwind CSS 4, Livewire 4, Laravel Breeze.
   - Buat Migrations & Models lengkap (sama spt versi sebelumnya).

2. **Phase 2: Dashboard Layout**
   - Bangun master layout tanpa sidebar (Top-nav UI).
   - Component `Livewire\Dashboard` (Card list, Create Modal).

3. **Phase 3: CSS Tree & Canvas**
   - Route `/tree/{id}`, Recursive Blade `<x-tree-node>`.
   - Embed panzoom.js.
   - **PENTING:** Gunakan `horizontal_view.html` (untuk tree canvas dan node classes seperti `.haswife`, `.partner`, `.pt-thumb`) dan `vertical_view.html` sebagai referensi markup HTML/CSS utama agar desain persis sama.

4. **Phase 4: Member CRUD Modal**
   - Modal 3-tab (Pribadi, Kontak, Biografi).
   - Avatar preset picker & photo upload logic.
   - Relationship assignment logic (Anak/Pasangan/Mantan/Parent).

5. **Phase 5: Export & Public View**
   - `/s/{slug}`, html2canvas integration.
