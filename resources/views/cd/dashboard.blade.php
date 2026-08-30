@extends('layouts.app')

@section('title', 'Dashboard Casting Director')

@section('content')
<p style="color: var(--text-secondary); margin: -8px 0 20px; font-size: 13.5px;">
    Halo, {{ auth()->user()->name }}.
</p>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <div class="metric-card">
        <div class="metric-label">Perlu Direview</div>
        <div class="metric-value">{{ $perluDireview }}</div>
    </div>
</div>

<div class="dashboard-grid-2col is-even">
    <div class="card">
        <div class="card-title">Keputusan Greenlight Saya</div>
        <div class="chart-box"><canvas id="chartKeputusan"></canvas></div>
    </div>
    <div class="card" style="display: flex; align-items: center; justify-content: center;">
        <a href="{{ route('cd.reviews.index') }}" class="btn btn-brand">Buka Greenlight</a>
    </div>
</div>
@endsection

@push('scripts')
<script>
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    var textColor = isDark ? '#9db3a2' : '#435449';

    new Chart(document.getElementById('chartKeputusan'), {
        type: 'doughnut',
        data: {
            labels: @json($chartKeputusan['labels']),
            datasets: [{
                data: @json($chartKeputusan['data']),
                backgroundColor: ['#22c55e', '#ef4444'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { color: textColor } } }
        }
    });
</script>
@endpush
