{{-- CD: tombol "Hubungi via WA" khusus Admin/SA. Param: user (Extras), pesanWa? (default: pesan pembuka umum). Nomor tidak dirender selain di tautan ini. --}}
@php
    $pengirim = auth()->user();
    $tautanWa = $pengirim?->bisaSebagaiAdmin()
        ? $user?->tautanWa($pesanWa ?? "Halo {$user->name}, saya {$pengirim->name} dari JBTB Casting. Ada kabar soal casting, apakah kamu sedang available?")
        : null;
@endphp
@if ($tautanWa)
    <a href="{{ $tautanWa }}" target="_blank" rel="noopener" class="btn btn-sm"><i class="ti ti-brand-whatsapp" aria-hidden="true"></i> Hubungi via WA</a>
@endif
