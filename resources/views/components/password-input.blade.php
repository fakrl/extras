@props(['name' => 'password', 'label' => 'Password', 'required' => true, 'minlength' => null])

@php
    $inputId = 'pwd-' . $name;
@endphp

<label for="{{ $inputId }}">{{ $label }}</label>
<div class="password-field-wrap">
    <div class="password-field">
        <input
            type="password"
            id="{{ $inputId }}"
            name="{{ $name }}"
            @if ($required) required @endif
            @if ($minlength) minlength="{{ $minlength }}" @endif
            {{ $attributes }}
        >
        <button type="button" class="password-toggle" onclick="togglePasswordField(this)" tabindex="-1" aria-label="Tampilkan/sembunyikan password">
            <i class="ti ti-eye"></i>
        </button>
    </div>
</div>

@once
    @push('scripts')
    <script>
        function togglePasswordField(btn) {
            var input = btn.closest('.password-field').querySelector('input');
            var icon = btn.querySelector('i');
            var isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            icon.className = isHidden ? 'ti ti-eye-off' : 'ti ti-eye';
        }
    </script>
    @endpush
@endonce
