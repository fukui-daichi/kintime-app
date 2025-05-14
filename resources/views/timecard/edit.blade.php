<x-app-layout>
    <x-header :user="$user" />
    <x-manager.sidebar />

    <x-main-content>
        <div class="max-w-7xl">
            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                勤怠編集
                            </h2>
                        </header>

                        <form method="POST" action="{{ route('timecard.update', ['timecard' => $timecard['id']]) }}" class="mt-6 space-y-6">
                            @csrf
                            @method('PUT')

                            <input type="hidden" name="year" value="{{ $year }}">
                            <input type="hidden" name="month" value="{{ $month }}">

                            <div>
                                <x-input-label for="date_formatted" :value="__('日付')" />
                                <x-text-input id="date_formatted" type="text" class="mt-1 block w-full bg-gray-100 dark:bg-gray-700"
                                    value="{{ $timecard['date_formatted'] }}" readonly />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="clock_in" :value="__('出勤時間')" />
                                    <x-text-input id="clock_in" name="clock_in" type="time" class="mt-1 block w-full"
                                        :value="old('clock_in', $timecard['clock_in'])" required />
                                    <x-input-error class="mt-2" :messages="$errors->get('clock_in')" />
                                </div>
                                <div>
                                    <x-input-label for="clock_out" :value="__('退勤時間')" />
                                    <x-text-input id="clock_out" name="clock_out" type="time" class="mt-1 block w-full"
                                        :value="old('clock_out', $timecard['clock_out'])" required />
                                    <x-input-error class="mt-2" :messages="$errors->get('clock_out')" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="break_start" :value="__('休憩開始')" />
                                    <x-text-input id="break_start" name="break_start" type="time" class="mt-1 block w-full"
                                        :value="old('break_start', $timecard['break_start'])" required />
                                    <x-input-error class="mt-2" :messages="$errors->get('break_start')" />
                                </div>
                                <div>
                                    <x-input-label for="break_end" :value="__('休憩終了')" />
                                    <x-text-input id="break_end" name="break_end" type="time" class="mt-1 block w-full"
                                        :value="old('break_end', $timecard['break_end'])" required />
                                    <x-input-error class="mt-2" :messages="$errors->get('break_end')" />
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <x-primary-button>更新</x-primary-button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </x-main-content>
</x-app-layout>
