@props(['name' => 'password', 'label' => 'Kata Sandi', 'required' => true, 'minlength' => null, 'hint' => null])

@php
    $inputId = 'pwd-' . $name;
@endphp

<label for="{{ $inputId }}">{{ $label }}@if ($required) <span class="wajib" aria-hidden="true">*</span>@endif</label>
<div class="password-field-wrap">
    <div class="password-field">
        <input
            type="password"
            id="{{ $inputId }}"
            name="{{ $name }}"
            @if ($required) required @endif
            @if ($minlength) minlength="{{ $minlength }}" @endif
            @if ($hint) aria-describedby="{{ $inputId }}-hint" @endif
            {{ $attributes }}
        >
        <button type="button" class="password-toggle" onclick="togglePasswordField(this)" aria-label="Tampilkan kata sandi" aria-pressed="false">
            <i class="ti ti-eye" aria-hidden="true"></i>
        </button>
    </div>
</div>
@if ($hint)
    <p class="field-hint" id="{{ $inputId }}-hint">{{ $hint }}</p>
@endif

@once
    @push('scripts')
    <script>
        function togglePasswordField(btn) {
            var input = btn.closest('.password-field').querySelector('input');
            var icon = btn.querySelector('i');
            var isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            icon.className = isHidden ? 'ti ti-eye-off' : 'ti ti-eye';
            btn.setAttribute('aria-pressed', isHidden);
            btn.setAttribute('aria-label', isHidden ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        }
    </script>
    @endpush
@endonce
