<button {{ $attributes->merge([
    'type' => 'button',
    'class' => 'inline-flex items-center px-4 py-2 bg-orange-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition ease-in-out duration-150 ' .
    ($attributes->get('disabled') ? 'opacity-70 cursor-not-allowed' : 'hover:bg-orange-600 focus:bg-orange-600 active:bg-orange-700')
]) }}>
    {{ $slot }}
</button>
