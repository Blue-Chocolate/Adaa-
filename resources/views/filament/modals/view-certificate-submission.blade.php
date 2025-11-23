<div class="space-y-6">
    {{-- Organization Overview --}}
    <div class="grid grid-cols-3 gap-4 p-4 bg-gray-50 rounded-lg">
        <div>
            <p class="text-sm text-gray-600">Organization</p>
            <p class="font-semibold text-gray-900">{{ $organization->name }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Final Score</p>
            <p class="font-semibold">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                    @if($organization->certificate_final_score >= 90) bg-green-100 text-green-800
                    @elseif($organization->certificate_final_score >= 70) bg-blue-100 text-blue-800
                    @else bg-yellow-100 text-yellow-800
                    @endif">
                    {{ $organization->certificate_final_score ? round($organization->certificate_final_score, 2) . '%' : 'Not scored' }}
                </span>
            </p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Final Rank</p>
            <p class="font-semibold">
                @if($organization->certificate_final_rank)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        @if($organization->certificate_final_rank === 'diamond') bg-blue-100 text-blue-800
                        @elseif($organization->certificate_final_rank === 'gold') bg-yellow-100 text-yellow-800
                        @elseif($organization->certificate_final_rank === 'silver') bg-gray-100 text-gray-800
                        @elseif($organization->certificate_final_rank === 'bronze') bg-orange-100 text-orange-800
                        @endif">
                        {{ ucfirst($organization->certificate_final_rank) }}
                    </span>
                @else
                    <span class="text-gray-500">No Rank</span>
                @endif
            </p>
        </div>
    </div>

    {{-- Path Status Overview --}}
    <div class="grid grid-cols-3 gap-4">
        {{-- Strategic Path --}}
        <div class="border border-gray-200 rounded-lg p-4">
            <h4 class="font-semibold text-gray-900 mb-2">Strategic Path</h4>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Score:</span>
                    <span class="font-medium">{{ $organization->certificate_strategic_score ? round($organization->certificate_strategic_score, 2) . '%' : 'N/A' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Status:</span>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                        {{ $organization->certificate_strategic_submitted ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700' }}">
                        {{ $organization->certificate_strategic_submitted ? 'Submitted' : 'Not Submitted' }}
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Approval:</span>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                        {{ $organization->certificate_strategic_approved ? 'bg-green-100 text-green-700' : ($organization->certificate_strategic_submitted ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700') }}">
                        {{ $organization->certificate_strategic_approved ? 'Approved' : ($organization->certificate_strategic_submitted ? 'Pending' : 'N/A') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Operational Path --}}
        <div class="border border-gray-200 rounded-lg p-4">
            <h4 class="font-semibold text-gray-900 mb-2">Operational Path</h4>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Score:</span>
                    <span class="font-medium">{{ $organization->certificate_operational_score ? round($organization->certificate_operational_score, 2) . '%' : 'N/A' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Status:</span>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                        {{ $organization->certificate_operational_submitted ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700' }}">
                        {{ $organization->certificate_operational_submitted ? 'Submitted' : 'Not Submitted' }}
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Approval:</span>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                        {{ $organization->certificate_operational_approved ? 'bg-green-100 text-green-700' : ($organization->certificate_operational_submitted ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700') }}">
                        {{ $organization->certificate_operational_approved ? 'Approved' : ($organization->certificate_operational_submitted ? 'Pending' : 'N/A') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- HR Path --}}
        <div class="border border-gray-200 rounded-lg p-4">
            <h4 class="font-semibold text-gray-900 mb-2">HR Path</h4>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Score:</span>
                    <span class="font-medium">{{ $organization->certificate_hr_score ? round($organization->certificate_hr_score, 2) . '%' : 'N/A' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Status:</span>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                        {{ $organization->certificate_hr_submitted ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700' }}">
                        {{ $organization->certificate_hr_submitted ? 'Submitted' : 'Not Submitted' }}
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Approval:</span>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                        {{ $organization->certificate_hr_approved ? 'bg-green-100 text-green-700' : ($organization->certificate_hr_submitted ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700') }}">
                        {{ $organization->certificate_hr_approved ? 'Approved' : ($organization->certificate_hr_submitted ? 'Pending' : 'N/A') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Answers by Path --}}
    @foreach(['strategic' => 'Strategic Performance', 'operational' => 'Operational Performance', 'hr' => 'Human Resources'] as $path => $pathLabel)
        @php
            $pathAnswers = $organization->certificateAnswers->filter(fn($answer) => $answer->question->path === $path);
        @endphp

        @if($pathAnswers->isNotEmpty())
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="bg-gray-100 p-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $pathLabel }}</h3>
                </div>

                <div class="p-4 space-y-4">
                    @php
                        $groupedByAxis = $pathAnswers->groupBy(fn($answer) => $answer->question->axis->title ?? 'Unknown Axis');
                    @endphp

                    @foreach($groupedByAxis as $axisTitle => $axisAnswers)
                        <div class="border border-gray-200 rounded-lg p-4">
                            <h4 class="font-semibold text-gray-900 mb-3">{{ $axisTitle }}</h4>
                            
                            <div class="space-y-3">
                                @foreach($axisAnswers as $index => $answer)
                                    <div class="p-3 bg-gray-50 rounded-lg">
                                        <p class="font-medium text-gray-900 mb-2">
                                            {{ $loop->iteration }}. {{ $answer->question->question }}
                                        </p>
                                        
                                        <div class="flex items-center gap-4">
                                            <div>
                                                <span class="text-sm text-gray-600">Answer:</span>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700 ml-2">
                                                    {{ $answer->selected_option }}
                                                </span>
                                            </div>
                                            
                                            @if($answer->attachment_url)
                                                <a href="{{ $answer->attachment_url }}" 
                                                   target="_blank"
                                                   class="inline-flex items-center px-3 py-1 text-sm font-medium text-green-600 bg-green-50 rounded-lg hover:bg-green-100">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                                    </svg>
                                                    {{ basename($answer->attachment_url) }}
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach

    @if($organization->certificateAnswers->isEmpty())
        <div class="text-center py-8 text-gray-500">
            No certificate answers submitted yet.
        </div>
    @endif
</div>