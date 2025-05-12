@props([
    'width' => 'max-w-7xl',
    'padding' => 'p-6',
    'background' => 'bg-white dark:bg-gray-800'
])

<main {{ $attributes->merge([
    'class' => "{$padding} md:ml-64 min-h-screen h-auto pt-20 {$background}"
]) }}>
    {{ $slot }}
</main>
