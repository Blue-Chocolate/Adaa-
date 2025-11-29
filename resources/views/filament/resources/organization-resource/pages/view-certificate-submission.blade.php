<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Organization Header --}}
        <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-950 dark:text-white">
                    {{ $this->record->name }}
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Shield Submission Details
                </p>
            </div>
        </div>

        {{-- Shield Metrics --}}
        <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-950 dark:text-white mb-4">
                    Shield Performance
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Shield Percentage</p>
                        <p class="text-3xl font-bold text-gray-950 dark:text-white mt-1">
                            {{ $this->record->shield_percentage ?? 'N/A' }}@if($this->record->shield_percentage)%@endif
                        </p>
                    </div>
                    
                    <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Shield Rank</p>
                        @if($this->record->shield_rank)
                            <span class="inline-flex items-center px-3 py-2 rounded-full text-lg font-semibold mt-2
                                @if($this->record->shield_rank === 'bronze') bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300
                                @elseif($this->record->shield_rank === 'silver') bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                @elseif($this->record->shield_rank === 'gold') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                @elseif($this->record->shield_rank === 'diamond') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                @endif">
                                {{ ucfirst($this->record->shield_rank) }}
                            </span>
                        @else
                            <p class="text-2xl font-bold text-gray-950 dark:text-white mt-1">Not Ranked</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Shield Axis Responses --}}
        @php
            $groupedResponses = $this->record->shieldAxisResponses->groupBy(function($response) {
                return $response->axis->name ?? 'Uncategorized';
            });
        @endphp

        @forelse($groupedResponses as $axisName => $responses)
            <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-950 dark:text-white">
                            {{ $axisName }}
                        </h3>
                        @php
                            $axisScore = $responses->first()->axis_score ?? null;
                        @endphp
                        @if($axisScore !== null)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                Axis Score: {{ $axisScore }}%
                            </span>
                        @endif
                    </div>
                    
                    <div class="space-y-6">
                        @foreach($responses as $response)
                            <div class="border-l-4 border-green-500 pl-4 py-2">
                                @php
                                    $question = $response->axis->questions->find($response->question_id);
                                @endphp
                                
                                <p class="text-sm font-medium text-gray-950 dark:text-white mb-2">
                                    {{ $question->question_text ?? 'Question not found' }}
                                </p>
                                
                                @if($question && $question->question_type === 'multiple_choice')
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        <span class="font-medium">Answer:</span> {{ $response->answer_text ?? 'N/A' }}
                                    </p>
                                @elseif($question && $question->question_type === 'file_upload')
                                    @if($response->file_path)
                                        <div class="mt-2">
                                            <a href="{{ Storage::url($response->file_path) }}" 
                                               target="_blank"
                                               class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                View Uploaded File
                                            </a>
                                        </div>
                                    @else
                                        <p class="text-sm text-gray-500 dark:text-gray-400">No file uploaded</p>
                                    @endif
                                @elseif($question && $question->question_type === 'text')
                                    <p class="text-sm text-gray-700 dark:text-gray-300 mt-1 whitespace-pre-wrap">
                                        {{ $response->answer_text ?? 'No answer provided' }}
                                    </p>
                                @endif

                                @if($response->score !== null)
                                    <div class="mt-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                            Score: {{ $response->score }}%
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="p-6 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        No shield responses found for this organization.
                    </p>
                </div>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>