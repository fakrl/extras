@extends('errors.layout')

@section('judul', 'Akses Ditolak')
@section('isi', (($exception ?? null)?->getMessage() ?: null) ?? 'Akunmu tidak punya izin untuk membuka halaman ini. Kalau merasa ini keliru, hubungi Admin JBTB Casting.')
