@props(['cor' => 'secondary'])

<span {{ $attributes->merge(['class' => "badge text-bg-{$cor}"]) }}>{{ $slot }}</span>
