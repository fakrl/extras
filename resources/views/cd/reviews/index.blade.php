@extends('layouts.app')

@section('title', 'Review Kandidat')

@section('content')
<form method="POST" action="{{ route('cd.reviews.review') }}">
    @csrf

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th><input type="checkbox" id="check-all"></th>
                    <th>Foto</th><th>Alias</th><th>Proyek</th><th>Foto Lain</th><th>Video</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($applications as $app)
                    <tr>
                        <td><input type="checkbox" name="application_ids[]" value="{{ $app->id }}" class="app-checkbox"></td>
                        <td>
                            @if ($app->extras->foto_profil_path)
                                <img src="{{ route('extras.media.foto', $app->extras) }}" alt="Foto {{ $app->extras->alias }}" class="thumb-photo">
                            @else
                                <div class="thumb-photo thumb-photo-empty"><i class="ti ti-user"></i></div>
                            @endif
                        </td>
                        <td>{{ $app->extras->alias }}</td>
                        <td>{{ $app->castingProject->nama_produksi }}</td>
                        <td>
                            @forelse ($app->extras->photos as $foto)
                                <a href="{{ route('extras.media.foto-tambahan', [$app->extras, $foto->urutan]) }}" target="_blank">
                                    <img src="{{ route('extras.media.foto-tambahan', [$app->extras, $foto->urutan]) }}" alt="Foto {{ $foto->urutan }}" class="thumb-photo-mini">
                                </a>
                            @empty
                                <span style="color: var(--text-muted); font-size: 12px;">-</span>
                            @endforelse
                        </td>
                        <td>
                            @if ($app->extras->video_profil_path)
                                <a href="{{ route('extras.media.video', $app->extras) }}" target="_blank" class="btn btn-sm">Lihat Video</a>
                            @else
                                <span style="color: var(--text-muted); font-size: 12px;">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center; color: var(--text-muted); padding: 20px 0;">Tidak ada kandidat yang perlu direview.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($applications->isNotEmpty())
        <div style="margin-top: 14px; display: flex; gap: 8px;">
            <button type="submit" name="keputusan" value="approve" class="btn btn-brand">Approve Terpilih</button>
            <button type="submit" name="keputusan" value="reject" class="btn btn-danger-outline">Reject Terpilih</button>
        </div>
    @endif
</form>
@endsection

@push('scripts')
<script>
    document.getElementById('check-all')?.addEventListener('change', function (e) {
        document.querySelectorAll('.app-checkbox').forEach(function (cb) {
            cb.checked = e.target.checked;
        });
    });
</script>
@endpush
