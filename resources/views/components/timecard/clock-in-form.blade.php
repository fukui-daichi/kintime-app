<form action="{{ route('timecard.clock-in') }}" method="POST">
    @csrf
    <button type="submit"
        @if($disabled ?? false) disabled @endif
        class="inline-flex items-center px-4 py-2 bg-blue-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed">
        出勤打刻
    </button>
</form>
