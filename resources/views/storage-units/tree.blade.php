<ul class="list-unstyled {{ $depth > 0 ? 'ms-3 ms-sm-4 border-start ps-2 ps-sm-3' : 'mb-0' }}">
    @foreach($units as $unit)
        <li class="py-1">
            <a href="{{ route('storage-units.show', $unit) }}" class="text-decoration-none">
                <i class="fa {{ $unit->level->icon() }} text-muted me-1" title="{{ $unit->level->label() }}"></i>
                <span class="{{ $depth === 0 ? 'fw-bold' : '' }}">{{ $unit->name }}</span>
                @unless($unit->level->holdsStock())
                    <code class="small ms-1">{{ $unit->code }}</code>
                @endunless
            </a>
            @if($unit->level->holdsStock())
                <span class="badge bg-light text-dark border ms-1">{{ $unit->stocks_count }} Material</span>
            @endif
            @if($unit->relationLoaded('children') && $unit->children->isNotEmpty())
                @include('storage-units.tree', ['units' => $unit->children, 'depth' => $depth + 1])
            @endif
        </li>
    @endforeach
</ul>
