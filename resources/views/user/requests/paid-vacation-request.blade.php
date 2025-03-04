<x-user-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            有給休暇申請
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{-- 日付表示 --}}
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">対象日</h3>
                        <p class="text-gray-900 text-lg font-medium">{{ $displayDate }}</p>
                    </div>

                    {{-- エラーメッセージの表示 --}}
                    @if ($errors->any())
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- 申請フォーム --}}
                    <form method="POST" action="{{ route('requests.store') }}" class="space-y-6">
                        @csrf
                        {{-- hidden フィールド --}}
                        <input type="hidden" name="target_date" value="{{ $targetDate }}">
                        <input type="hidden" name="request_type" value="{{ $defaultRequestType }}">

                        {{-- 有給休暇種別 --}}
                        <div>
                            <label for="vacation_type" class="block text-sm font-medium text-gray-700">休暇種別</label>
                            <select id="vacation_type"
                                    name="vacation_type"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @foreach($vacationTypes as $value => $label)
                                    <option value="{{ $value }}" {{ old('vacation_type') == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 申請理由 --}}
                        <div>
                            <label for="reason" class="block text-sm font-medium text-gray-700">申請理由</label>
                            <textarea id="reason"
                                    name="reason"
                                    rows="3"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="申請理由を入力してください">{{ old('reason') }}</textarea>
                        </div>

                        {{-- 送信ボタン --}}
                        <div class="flex justify-end space-x-4">
                            <a href="{{ route('timecard.index') }}"
                            class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                                キャンセル
                            </a>
                            <button type="submit"
                                    class="inline-flex justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                申請する
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-user-layout>
