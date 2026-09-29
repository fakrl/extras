@props(['paginator', 'pilihan', 'form' => 'live-form'])
<div class="pagebar">
    <span class="pagebar-info">{{ $paginator->total() ? 'Menampilkan '.$paginator->firstItem().'–'.$paginator->lastItem().' dari '.$paginator->total() : '0 hasil' }}</span>
    {{ $paginator->links() }}
    <x-per-halaman :pilihan="$pilihan" :nilai="$paginator->perPage()" :form="$form" />
</div>
