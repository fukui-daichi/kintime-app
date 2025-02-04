<div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full"
    id="{{ $id }}"
    style="display: none;">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900">{{ $title }}</h3>
            <div class="mt-2 px-7 py-3">
                {{ $slot }}
            </div>
            <div class="items-center px-4 py-3">
                {{ $footer }}
            </div>
        </div>
    </div>
</div>
