@extends('layouts.app')

@section('title', 'Monitoring · Korlap')

@include('super-admin.monitoring._gaya')

@section('content')
@include('super-admin.monitoring._kepala', ['mode' => 'korlap', 'teks' => 'Pratinjau lapangan, lihat saja. Klik baris untuk masuk mode Korlap langsung di halaman absensinya.'])

@include('partials.korlap-ringkasan', ['masuk' => true])
@endsection
