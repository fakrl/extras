@props(['model'])

<span {{ $attributes->merge(['class' => 'badge '.$model->badgeClass()]) }}>{{ $model->label() }}</span>
