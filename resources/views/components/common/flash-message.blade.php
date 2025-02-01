@props(['type' => 'success', 'message'])

<div x-data="{ show: true }"
     x-show="show"
     x-init="setTimeout(() => { show = true; setTimeout(() => show = false, 3000); }, 50)"
     x-transition:leave="transform transition-all duration-500 ease-in-out"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-[-20px]"
>
    <div class="rounded-lg p-4 mb-4 text-sm {{ $type === 'success'
        ? 'bg-green-100 text-green-700'
        : 'bg-red-100 text-red-700' }}">
        {{ $message }}
    </div>
</div>
