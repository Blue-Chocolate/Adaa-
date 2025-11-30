<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - {{ $certificate->certificate_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            margin: 0;
            size: A4 landscape;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            width: 297mm;
            height: 210mm;
            position: relative;
            overflow: hidden;
        }

        .certificate-container {
            width: 100%;
            height: 100%;
            position: relative;
            background-color: {{ $data['background_color'] ?? '#ffffff' }};
            @if(isset($data['background_image']) && $data['background_image'])
            background-image: url('{{ Storage::url($data['background_image']) }}');
            background-size: cover;
            background-position: center;
            @endif
        }

        /* Border styling based on rank */
        .certificate-border {
            position: absolute;
            top: 20mm;
            left: 20mm;
            right: 20mm;
            bottom: 20mm;
            @php
                $rank = $data['rank'] ?? 'bronze';
                $border = $data['borders'][$rank] ?? ['color' => '#000000', 'width' => 8, 'style' => 'solid'];
            @endphp
            border: {{ $border['width'] }}px {{ $border['style'] }} {{ $border['color'] }};
            padding: 15mm;
        }

        /* Logo positioning */
        .certificate-logo {
            position: absolute;
            @php
                $logoPos = $data['logo_settings']['position'] ?? 'top-center';
                $logoSize = $data['logo_settings']['size'] ?? 80;
            @endphp
            width: {{ $logoSize }}px;
            height: {{ $logoSize }}px;
            object-fit: contain;
            
            @if($logoPos === 'top-left')
                top: 30mm;
                left: 30mm;
            @elseif($logoPos === 'top-center')
                top: 30mm;
                left: 50%;
                transform: translateX(-50%);
            @elseif($logoPos === 'top-right')
                top: 30mm;
                right: 30mm;
            @elseif($logoPos === 'bottom-center')
                bottom: 30mm;
                left: 50%;
                transform: translateX(-50%);
            @endif
        }

        /* Text elements */
        .text-element {
            position: absolute;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="certificate-border">
            
            {{-- Organization Logo --}}
            @if($certificate->organization_logo_path)
                <img src="{{ Storage::url($certificate->organization_logo_path) }}" 
                     class="certificate-logo" 
                     alt="Organization Logo">
            @endif

            {{-- Dynamic Text Elements --}}
            @foreach($data['elements'] as $element)
                <div class="text-element" style="
                    left: {{ $element['x'] }}%;
                    top: {{ $element['y'] }}%;
                    font-size: {{ $element['fontSize'] }}px;
                    font-family: {{ $element['fontFamily'] }};
                    color: {{ $element['color'] }};
                    text-align: {{ $element['align'] }};
                    font-weight: {{ ($element['bold'] ?? false) ? 'bold' : 'normal' }};
                    transform: translate(-50%, -50%);
                    max-width: 80%;
                ">
                    {!! nl2br(e($element['content'])) !!}
                </div>
            @endforeach

        </div>
    </div>
</body>
</html>