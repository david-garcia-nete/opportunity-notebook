@if ($items->isEmpty())
    <div class="mt-4 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">
        {{ $emptyMessage }}
    </div>
@else
    <ol class="mt-4 divide-y divide-gray-100 rounded-xl border border-gray-100 bg-white">
        @foreach ($items as $item)
            <li class="p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <time datetime="{{ $item['date']->toDateString() }}" class="text-sm font-semibold text-gray-900">{{ $item['date']->toFormattedDateString() }}</time>
                            <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-100">{{ $item['type_label'] }}</span>
                            @if ($item['status'])
                                <span class="rounded-full bg-gray-50 px-2.5 py-1 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-200">{{ $item['status'] }}</span>
                            @endif
                        </div>
                        <p class="mt-2 text-sm font-semibold text-gray-900">
                            @if ($item['url'])
                                <a href="{{ $item['url'] }}" class="text-indigo-600 hover:text-indigo-900">{{ $item['title'] }}</a>
                            @else
                                {{ $item['title'] }}
                            @endif
                        </p>
                        <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-500">
                            @if ($item['opportunity'])
                                <span>Opportunity: <a href="{{ route('opportunities.show', $item['opportunity']) }}" class="font-medium text-indigo-600 hover:text-indigo-900">{{ $item['opportunity']->title }}</a></span>
                            @endif
                            @if ($item['contact'])
                                <span>Contact: {{ $item['contact'] }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </li>
        @endforeach
    </ol>
@endif
