<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Rank Selector --}}
        <div class="flex gap-3">
            @foreach(['diamond', 'gold', 'silver', 'bronze'] as $rank)
                <button
                    wire:click="setRank('{{ $rank }}')"
                    class="px-4 py-2 rounded-lg font-medium capitalize transition-all {{ $selectedRank === $rank ? 'ring-2 ring-offset-2' : 'opacity-60' }}"
                    style="background-color: {{ $record->getBorderForRank($rank)['color'] }}; color: white;"
                >
                    {{ ucfirst($rank) }}
                </button>
            @endforeach
        </div>

        {{-- Certificate Preview --}}
        <div class="bg-white p-8 rounded-lg shadow">
            <div 
                class="relative mx-auto"
                style="
                    width: 100%;
                    max-width: 800px;
                    aspect-ratio: 1.414 / 1;
                    background-color: {{ $record->background_color }};
                    border: {{ $record->getBorderForRank($selectedRank)['width'] }}px {{ $record->getBorderForRank($selectedRank)['style'] }} {{ $record->getBorderForRank($selectedRank)['color'] }};
                "
            >
                {{-- Logo --}}
                @php
                    $logoPos = $record->getLogoPosition();
                    $logoStyle = '';
                    foreach ($logoPos as $key => $value) {
                        $logoStyle .= "$key: $value; ";
                    }
                @endphp
                <div 
                    class="absolute flex items-center justify-center bg-gray-300 rounded-full"
                    style="{{ $logoStyle }} width: {{ $record->logo_settings['size'] }}px; height: {{ $record->logo_settings['size'] }}px;"
                >
                    <span style="font-size: {{ $record->logo_settings['size'] / 4 }}px;" class="font-bold text-gray-600">LOGO</span>
                </div>

                {{-- Text Elements --}}
                @foreach($record->elements as $element)
                    <div 
                        class="absolute"
                        style="
                            left: {{ $element['x'] }}%;
                            top: {{ $element['y'] }}%;
                            transform: translate(-50%, -50%);
                            font-size: {{ $element['fontSize'] }}px;
                            font-family: {{ $element['fontFamily'] }};
                            color: {{ $element['color'] }};
                            text-align: {{ $element['align'] }};
                            font-weight: {{ $element['bold'] ? 'bold' : 'normal' }};
                            white-space: nowrap;
                            padding: 4px 8px;
                        "
                    >
                        {{ $element['content'] }}
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Placeholder Reference --}}
        <div class="bg-blue-50 p-4 rounded-lg">
            <h4 class="font-semibold text-blue-900 mb-2">Available Placeholders:</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm text-blue-800">
                <code class="bg-blue-100 px-2 py-1 rounded">[Organization Name]</code>
                <code class="bg-blue-100 px-2 py-1 rounded">[Rank]</code>
                <code class="bg-blue-100 px-2 py-1 rounded">[Score]</code>
                <code class="bg-blue-100 px-2 py-1 rounded">[License Number]</code>
                <code class="bg-blue-100 px-2 py-1 rounded">[Certificate Number]</code>
                <code class="bg-blue-100 px-2 py-1 rounded">[Date]</code>
                <code class="bg-blue-100 px-2 py-1 rounded">[Path]</code>
                <code class="bg-blue-100 px-2 py-1 rounded">[Issued By]</code>
            </div>
        </div>
    </div>
</x-filament-panels::page>