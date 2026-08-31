<div class="mb-3">
    <label class="form-label">Materialien</label>
    <input type="text" id="itemSearch" class="form-control" placeholder="Suchen…" oninput="filterItems()" autofocus />
</div>

<div id="itemsContainer">
    @php
        $grouped = $items->groupBy(fn($i) => optional($i->itemGroup)->position . '|' . optional($i->itemGroup)->name);
        $orderItemQuantities = isset($order) ? $order->orderedItems->pluck('quantity', 'item_id') : collect();
    @endphp

    @foreach($grouped as $groupKey => $groupItems)
        @php [$pos, $groupName] = array_pad(explode('|', $groupKey, 2), 2, 'Sonstiges') @endphp
        <div class="card mb-3 item-group">
            <div class="card-header">{{ $groupName }}</div>
            <div class="card-body table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                    <tr>
                        <th scope="col" class="col-6">Name</th>
                        <th scope="col" class="col-2">Verfügbar</th>
                        <th scope="col" class="col-{{ isset($order) && isset($order->state) && ($order->state?->equals(\App\States\Order\Delivered::class) || $order->state?->equals(\App\States\Order\Returned::class) || $order->state?->equals(\App\States\Order\Archived::class)) ? '1' : '2' }}">Packungsgrösse</th>
                        <th scope="col" class="col-{{ isset($order) && isset($order->state) && ($order->state?->equals(\App\States\Order\Delivered::class) || $order->state?->equals(\App\States\Order\Returned::class) || $order->state?->equals(\App\States\Order\Archived::class)) ? '1' : '2' }}">Bestellen</th>
                        @if(isset($order) && isset($order->state) && ($order->state?->equals(\App\States\Order\Delivered::class) || $order->state?->equals(\App\States\Order\Returned::class) || $order->state?->equals(\App\States\Order\Archived::class)))
                            <th scope="col" class="col-2">Zurückgegeben</th>
                        @endif
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($groupItems as $item)
                        @php
                            $actualQty = old('items.'.$item->id, $orderItemQuantities->get($item->id, 0));
                            $displayQty = ($item->lot > 1) ? $actualQty / $item->lot : $actualQty;
                            $maxDisplayQty = ($item->lot > 1) ? floor($item->stock / $item->lot) : $item->stock;

                            $orderedItem = isset($order) && isset($order->orderedItems) ? $order->orderedItems->where('item_id', $item->id)->first() : null;
                            $returnedQty = old('returned.'.$item->id, $orderedItem?->returned ?? ($actualQty > 0 ? $actualQty : 0));
                            $displayReturnedQty = ($item->lot > 1) ? $returnedQty / $item->lot : $returnedQty;
                            $imageAttachments = $item->attachments->filter(fn($a) => \Illuminate\Support\Str::endsWith(strtolower($a->path), ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg']));
                            $imageUrls = $imageAttachments->map(function($img) {
                                try {
                                    return Storage::disk('s3')->temporaryUrl($img->path, now()->addMinutes(30));
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::error("S3 URL error for {$img->path}: " . $e->getMessage());
                                    return null;
                                }
                            })->filter();
                        @endphp
                        <tr class="item-row {{ $actualQty > 0 ? 'table-primary' : '' }}">
                            <td class="item-name">
                                {{ $item->name }}
                                @if($imageUrls->isNotEmpty())
                                    <i class="fa fa-image text-primary cursor-pointer ms-2"
                                       onclick="showImageCarousel({{ json_encode($imageUrls) }}, '{{ $item->name }}')"
                                       title="Bilder anzeigen"></i>
                                @endif
                                @if($item->attachments->count() > $imageAttachments->count())
                                    <i class="fa fa-file-alt text-muted ms-1" title="Hat weitere Anhänge"></i>
                                @endif

                                @if($item->contents->isNotEmpty())
                                    <i class="fa fa-info-circle text-info ms-1 cursor-pointer"
                                       data-bs-toggle="modal"
                                       data-bs-target="#contentModal"
                                       data-bs-title="Inhaltsliste von {{ $item->name }}"
                                       data-bs-content="<ul class='list-unstyled mb-0'>@foreach($item->contents as $c)<li><i class='fa fa-check-square-o me-1 small text-muted'></i><strong>{{ $c->quantity }}</strong> {{ $c->name }}</li>@endforeach</ul>"></i>
                                @endif
                            </td>
                            <td>{{ $item->stock }}</td>
                            <td>{{ $item->lot }}</td>
                            <td>
                                <input
                                    type="number"
                                    min="0"
                                    max="{{ $maxDisplayQty }}"
                                    class="form-control form-control-sm"
                                    name="items[{{ $item->id }}]"
                                    value="{{ $displayQty }}"
                                    {{ isset($order) && isset($order->state) && ($order->state?->equals(\App\States\Order\Delivered::class) || $order->state?->equals(\App\States\Order\Returned::class) || $order->state?->equals(\App\States\Order\Archived::class)) ? 'readonly' : '' }}
                                    onchange="updateItemHighlighting(this)"
                                    oninput="enforceQuantityLimits(this)"
                                    data-min-qty="0"
                                    data-max-qty="{{ $maxDisplayQty }}">
                            </td>
                            @if(isset($order) && isset($order->state) && ($order->state?->equals(\App\States\Order\Delivered::class) || $order->state?->equals(\App\States\Order\Returned::class) || $order->state?->equals(\App\States\Order\Archived::class)))
                                <td>
                                    @if($actualQty > 0)
                                        <input
                                            type="number"
                                            min="0"
                                            max="{{ $displayQty }}"
                                            class="form-control form-control-sm"
                                            name="returned[{{ $item->id }}]"
                                            value="{{ $displayReturnedQty }}"
                                            {{ $order->state?->equals(\App\States\Order\Returned::class) || $order->state?->equals(\App\States\Order\Archived::class) ? 'readonly' : '' }}
                                            oninput="enforceQuantityLimits(this)"
                                            data-min-qty="0"
                                            data-max-qty="{{ $displayQty }}">
                                    @else
                                        -
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>

<script>
    function updateItemHighlighting(input) {
        input.closest('.item-row').classList.toggle('table-primary', input.value > 0);
    }

    function enforceQuantityLimits(input) {
        const minQty = parseInt(input.dataset.minQty) || 0;
        const maxQty = parseInt(input.dataset.maxQty) || 999999;
        let currentVal = parseInt(input.value) || 0;

        if (currentVal < minQty) {
            input.value = minQty;
        } else if (currentVal > maxQty) {
            input.value = maxQty;
        }

        updateItemHighlighting(input);
    }

    function filterItems() {
        const q = (document.getElementById('itemSearch').value || '').toLowerCase();
        const groups = document.querySelectorAll('#itemsContainer .item-group');
        groups.forEach(group => {
            let visible = 0;
            group.querySelectorAll('.item-row').forEach(row => {
                const name = row.querySelector('.item-name').textContent.toLowerCase();
                const match = !q || name.includes(q);
                row.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            group.style.display = visible ? '' : 'none';
        });
    }
</script>

<!-- Image Carousel Modal -->
<div class="modal fade" id="imageCarouselModal" tabindex="-1" aria-labelledby="imageCarouselModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageCarouselModalLabel">Bilder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="itemImageCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner" id="carouselInner">
                        <!-- Images will be injected here -->
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#itemImageCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Zurück</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#itemImageCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Weiter</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function showImageCarousel(images, itemName) {
        const modalLabel = document.getElementById('imageCarouselModalLabel');
        const carouselInner = document.getElementById('carouselInner');
        const carousel = document.getElementById('itemImageCarousel');

        modalLabel.textContent = `Bilder: ${itemName}`;
        carouselInner.innerHTML = '';

        images.forEach((url, index) => {
            const div = document.createElement('div');
            div.className = `carousel-item ${index === 0 ? 'active' : ''}`;
            div.innerHTML = `<img src="${url}" class="d-block w-100" style="max-height: 80vh; object-fit: contain;" alt="${itemName}">`;
            carouselInner.appendChild(div);
        });

        // Hide controls if only one image
        const controls = carousel.querySelectorAll('.carousel-control-prev, .carousel-control-next');
        controls.forEach(control => {
            control.style.display = images.length > 1 ? '' : 'none';
        });

        const modal = new bootstrap.Modal(document.getElementById('imageCarouselModal'));
        modal.show();
    }
</script>
