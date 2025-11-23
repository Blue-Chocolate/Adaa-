<div class="space-y-6">
    {{-- Organization Overview --}}
    <div class="grid grid-cols-3 gap-4 p-4 bg-gray-50 rounded-lg">
        <div>
            <p class="text-sm text-gray-600">Organization</p>
            <p class="font-semibold text-gray-900">{{ $organization->name }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Shield Score</p>
            <p class="font-semibold">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                    @if($organization->shield_percentage >= 90) bg-green-100 text-green-800
                    @elseif($organization->shield_percentage >= 70) bg-blue-100 text-blue-800
                    @else bg-yellow-100 text-yellow-800
                    @endif">
                    {{ round($organization->shield_percentage, 2) }}%
                </span>
            </p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Shield Rank</p>
            <p class="font-semibold">
                @if($organization->shield_rank)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        @if($organization->shield_rank === 'gold') bg-yellow-100 text-yellow-800
                        @elseif($organization->shield_rank === 'silver') bg-gray-100 text-gray-800
                        @elseif($organization->shield_rank === 'bronze') bg-orange-100 text-orange-800
                        @endif">
                        {{ ucfirst($organization->shield_rank) }}
                    </span>
                @else
                    <span class="text-gray-500">No Rank</span>
                @endif
            </p>
        </div>
    </div>

    {{-- Axes Responses --}}
    @forelse($organization->shieldAxisResponses as $response)
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            {{-- Axis Header --}}
            <div class="bg-gray-100 p-4 border-b border-gray-200">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $response->axis->title }}</h3>
                        @if($response->axis->description)
                            <p class="text-sm text-gray-600 mt-1">{{ $response->axis->description }}</p>
                        @endif
                    </div>
                    <div class="ml-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                            @if($response->admin_score >= 75) bg-green-100 text-green-800
                            @elseif($response->admin_score >= 50) bg-yellow-100 text-yellow-800
                            @else bg-red-100 text-red-800
                            @endif">
                            Score: {{ round($response->admin_score, 2) }}%
                        </span>
                    </div>
                </div>
            </div>

            {{-- Questions & Answers --}}
            <div class="p-4 space-y-3">
                @php
                    $answers = is_array($response->answers) ? $response->answers : [];
                @endphp

                @foreach($response->axis->questions as $index => $question)
                    @php
                        $answer = $answers[$question->id] ?? null;
                    @endphp
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <div class="flex justify-between items-start">
                            <p class="font-medium text-gray-900 flex-1">
                                {{ $index + 1 }}. {{ $question->question }}
                            </p>
                            <span class="ml-3 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                @if($answer === true) bg-green-100 text-green-700
                                @elseif($answer === false) bg-red-100 text-red-700
                                @else bg-gray-100 text-gray-700
                                @endif">
                                @if($answer === true)
                                    Yes
                                @elseif($answer === false)
                                    No
                                @else
                                    Not Answered
                                @endif
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Attachments --}}
            @php
                $attachments = [];
                foreach ([1, 2, 3] as $num) {
                    $key = "attachment_{$num}";
                    if (isset($answers[$key]) && !empty($answers[$key])) {
                        $path = $answers[$key];
                        if (Storage::disk('public')->exists($path)) {
                            $attachments[] = [
                                'url' => Storage::disk('public')->url($path),
                                'name' => basename($path)
                            ];
                        }
                    }
                }
            @endphp

            @if(!empty($attachments))
                <div class="p-4 bg-blue-50 border-t border-gray-200">
                    <p class="text-sm font-medium text-gray-700 mb-2">Attachments:</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($attachments as $attachment)
                            <a href="{{ $attachment['url'] }}" 
                               target="_blank"
                               class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 bg-white border border-blue-200 rounded-lg hover:bg-blue-50">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                </svg>
                                {{ $attachment['name'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @empty
        <div class="text-center py-8 text-gray-500">
            No shield responses submitted yet.
        </div>
    @endforelse
</div>