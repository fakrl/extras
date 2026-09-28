@props(['action', 'method' => 'POST', 'message' => 'Yakin ingin melanjutkan?'])

<form {{ $attributes->merge(['action' => $action, 'method' => 'POST']) }}
      onsubmit="if(!confirm({{ \Illuminate\Support\Js::from($message) }})){return false;} var b=this.querySelector('[type=submit]'); if(b){b.disabled=true; b.dataset.origText=b.innerHTML; b.innerHTML='Memproses…';} return true;">
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif
    {{ $slot }}
</form>
