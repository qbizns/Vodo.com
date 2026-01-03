{{--
    Display Value Partial
    
    Renders a field value in read-only mode for detail/show views.
    
    Variables:
    - $value: mixed
    - $widget: string
    - $field: array - field configuration
--}}

@php
    $options = $field['options'] ?? [];
    $config = $field['config'] ?? [];
@endphp

@switch($widget)
    @case('checkbox')
    @case('boolean')
        @if($value)
            <span class="inline-flex items-center gap-1 text-green-600">
                @include('backend.partials.icon', ['icon' => 'check', 'class' => 'w-4 h-4'])
                Yes
            </span>
        @else
            <span class="inline-flex items-center gap-1 text-gray-500">
                @include('backend.partials.icon', ['icon' => 'x', 'class' => 'w-4 h-4'])
                No
            </span>
        @endif
        @break

    @case('selection')
    @case('select')
        @if($value && isset($options[$value]))
            <span class="badge">{{ $options[$value] }}</span>
        @elseif($value)
            <span>{{ ucfirst($value) }}</span>
        @else
            <span class="text-tertiary">-</span>
        @endif
        @break

    @case('badge')
    @case('statusbar')
        @php
            $colors = $config['colors'] ?? [
                'draft' => 'bg-gray-100 text-gray-800',
                'published' => 'bg-green-100 text-green-800',
                'active' => 'bg-green-100 text-green-800',
                'inactive' => 'bg-gray-100 text-gray-800',
                'suspended' => 'bg-red-100 text-red-800',
                'archived' => 'bg-yellow-100 text-yellow-800',
                'pending' => 'bg-blue-100 text-blue-800',
            ];
            $badgeClass = $colors[$value] ?? 'bg-gray-100 text-gray-800';
        @endphp
        @if($value)
            <span class="badge {{ $badgeClass }}">{{ ucfirst($value) }}</span>
        @else
            <span class="text-tertiary">-</span>
        @endif
        @break

    @case('date')
        @if($value)
            {{ \Carbon\Carbon::parse($value)->format($config['format'] ?? 'M j, Y') }}
        @else
            <span class="text-tertiary">-</span>
        @endif
        @break

    @case('datetime')
        @if($value)
            {{ \Carbon\Carbon::parse($value)->format($config['format'] ?? 'M j, Y g:i A') }}
        @else
            <span class="text-tertiary">-</span>
        @endif
        @break

    @case('time')
        @if($value)
            {{ $value }}
        @else
            <span class="text-tertiary">-</span>
        @endif
        @break

    @case('monetary')
    @case('float')
    @case('integer')
        @if($value !== null && $value !== '')
            @if($widget === 'monetary')
                <span class="font-mono">{{ $config['currency'] ?? '$' }}{{ number_format((float)$value, 2) }}</span>
            @elseif($widget === 'float')
                <span class="font-mono">{{ number_format((float)$value, $config['decimals'] ?? 2) }}</span>
            @else
                <span class="font-mono">{{ number_format((int)$value) }}</span>
            @endif
        @else
            <span class="text-tertiary">-</span>
        @endif
        @break

    @case('image')
        @if($value)
            <img src="{{ $value }}" 
                 alt="{{ $field['label'] ?? '' }}" 
                 class="max-w-xs rounded-lg border"
                 loading="lazy">
        @else
            <span class="text-tertiary">No image</span>
        @endif
        @break

    @case('binary')
    @case('file')
        @if($value)
            <a href="{{ $value }}" 
               target="_blank" 
               rel="noopener"
               class="inline-flex items-center gap-2 text-primary hover:underline">
                @include('backend.partials.icon', ['icon' => 'file', 'class' => 'w-4 h-4'])
                Download File
            </a>
        @else
            <span class="text-tertiary">No file</span>
        @endif
        @break

    @case('email')
        @if($value)
            <a href="mailto:{{ $value }}" class="text-primary hover:underline">{{ $value }}</a>
        @else
            <span class="text-tertiary">-</span>
        @endif
        @break

    @case('url')
        @if($value)
            <a href="{{ $value }}" 
               target="_blank" 
               rel="noopener noreferrer"
               class="text-primary hover:underline inline-flex items-center gap-1">
                {{ Str::limit($value, 50) }}
                @include('backend.partials.icon', ['icon' => 'externalLink', 'class' => 'w-3 h-3'])
            </a>
        @else
            <span class="text-tertiary">-</span>
        @endif
        @break

    @case('phone')
        @if($value)
            <a href="tel:{{ $value }}" class="text-primary hover:underline">{{ $value }}</a>
        @else
            <span class="text-tertiary">-</span>
        @endif
        @break

    @case('html')
        @if($value)
            <div class="prose prose-sm max-w-none">{!! $value !!}</div>
        @else
            <span class="text-tertiary">-</span>
        @endif
        @break

    @case('text')
        @if($value)
            <div class="whitespace-pre-wrap">{{ $value }}</div>
        @else
            <span class="text-tertiary">-</span>
        @endif
        @break

    @case('json')
        @if($value)
            <pre class="text-xs bg-gray-50 p-2 rounded overflow-auto max-h-48"><code>{{ is_string($value) ? $value : json_encode($value, JSON_PRETTY_PRINT) }}</code></pre>
        @else
            <span class="text-tertiary">-</span>
        @endif
        @break

    @case('color')
        @if($value)
            <span class="inline-flex items-center gap-2">
                <span class="w-6 h-6 rounded border" style="background-color: {{ $value }}"></span>
                <span class="font-mono text-sm">{{ $value }}</span>
            </span>
        @else
            <span class="text-tertiary">-</span>
        @endif
        @break

    @case('many2one')
    @case('relation')
        @if($value)
            @php
                $displayValue = is_object($value) ? ($value->name ?? $value->title ?? $value->id) : $value;
            @endphp
            <span>{{ $displayValue }}</span>
        @else
            <span class="text-tertiary">-</span>
        @endif
        @break

    @case('tags')
    @case('many2many')
        @if($value && (is_array($value) || $value instanceof \Illuminate\Support\Collection))
            <div class="flex flex-wrap gap-1">
                @foreach($value as $item)
                    <span class="tag">{{ is_object($item) ? ($item->name ?? $item->title ?? $item->id) : $item }}</span>
                @endforeach
            </div>
        @else
            <span class="text-tertiary">-</span>
        @endif
        @break

    @case('progressbar')
        @if($value !== null)
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="bg-primary h-2.5 rounded-full" style="width: {{ min(100, max(0, $value)) }}%"></div>
            </div>
            <span class="text-sm text-secondary">{{ $value }}%</span>
        @else
            <span class="text-tertiary">-</span>
        @endif
        @break

    @case('priority')
        @if($value !== null)
            <div class="flex gap-1">
                @for($i = 1; $i <= ($config['max'] ?? 5); $i++)
                    @if($i <= $value)
                        @include('backend.partials.icon', ['icon' => 'star', 'class' => 'w-4 h-4 text-yellow-400 fill-current'])
                    @else
                        @include('backend.partials.icon', ['icon' => 'star', 'class' => 'w-4 h-4 text-gray-300'])
                    @endif
                @endfor
            </div>
        @else
            <span class="text-tertiary">-</span>
        @endif
        @break

    @default
        @if($value !== null && $value !== '')
            {{ $value }}
        @else
            <span class="text-tertiary">-</span>
        @endif
@endswitch

