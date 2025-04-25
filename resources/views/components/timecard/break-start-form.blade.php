<form action="{{ route('timecard.break-start') }}" method="POST">
    @csrf
    <button type="submit"
        @if($disabled ?? false) disabled @endif
        class="inline-flex items-center px-4 py-2 bg-green-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed">
        休憩開始
    </button>
</form>
