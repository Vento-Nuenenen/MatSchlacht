<div class="mb-3">
    <label for="itemSearch" class="form-label fw-bold">Materialien filtern</label>
    <div class="input-group">
        <span class="input-group-text bg-white"><i class="fa fa-search text-muted"></i></span>
        <input type="text" id="itemSearch" class="form-control" placeholder="Material suchen…" oninput="filterItems()" autofocus />
    </div>
</div>

<div id="itemsContainer">
    @php
        $grouped = $items->groupBy(fn($i) => optional($i->itemGroup)->position . '|' . optional($i->itemGroup)->name);
        $orderItemQuantities = isset($order) ? $order->orderedItems->pluck('quantity', 'item_id') : collect();
    @endphp

    @foreach($grouped as $groupKey => $groupItems)
        @php [$pos, $groupName] = array_pad(explode('|', $groupKey, 2), 2, 'Sonstiges') @endphp
        <div class="card mb-3 item-group shadow-sm">
            <div class="card-header fw-bold bg-light">{{ $groupName }}</div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th scope="col" style="min-width: 140px;">Name</th>
                        <th scope="col" class="text-center" style="width: 15%; min-width: 75px;">Verfügbar</th>
                        <th scope="col" class="text-center" style="width: 15%; min-width: 75px;">Packungsgrösse</th>
                        <th scope="col" class="text-center" style="width: 15%; min-width: 85px;">Bestellen</th>
                        @if(isset($order) && isset($order->state) && ($order->state?->equals(\App\States\Order\Delivered::class) || $order->state?->equals(\App\States\Order\Returned::class) || $order->state?->equals(\App\States\Order\Archived::class)))
                            <th scope="col" class="text-center" style="width: 15%; min-width: 85px;">Zurückgegeben</th>
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
                            $imageUrls = $imageAttachments->map(fn ($img) => app(\App\Services\AttachmentService::class)->url($img))->filter();
                        @endphp
                        <tr class="item-row {{ $actualQty > 0 ? 'table-primary' : '' }}">
                            <td class="item-name py-2">
                                <span class="fw-semibold">{{ $item->name }}</span>
                                @if($imageUrls->isNotEmpty())
                                    <i class="fa fa-image text-primary cursor-pointer ms-2 p-1 item-carousel-trigger"
                                       data-images="{{ json_encode($imageUrls) }}"
                                       data-item-name="{{ $item->name }}"
                                       title="Bilder anzeigen"></i>
                                @endif
                                @if($item->attachments->count() > $imageAttachments->count())
                                    <i class="fa fa-file-alt text-muted ms-1 p-1" title="Hat weitere Anhänge"></i>
                                @endif

                                @if($item->contents->isNotEmpty())
                                    <i class="fa fa-info-circle text-info ms-1 cursor-pointer p-1"
                                       data-bs-toggle="modal"
                                       data-bs-target="#contentModal"
                                       data-bs-title="Inhaltsliste von {{ $item->name }}"
                                       data-bs-content="<ul class='list-unstyled mb-0'>@foreach($item->contents as $c)<li><i class='fa fa-check-square-o me-1 small text-muted'></i><strong>{{ $c->quantity }}</strong> {{ $c->name }}</li>@endforeach</ul>"></i>
                                @endif
                            </td>
                            <td class="text-center">{{ $item->stock }}</td>
                            <td class="text-center">{{ $item->lot }}</td>
                            <td class="text-center">
                                <input
                                    type="number"
                                    min="0"
                                    max="{{ $maxDisplayQty }}"
                                    class="form-control form-control-sm item-qty-input d-inline-block"
                                    name="items[{{ $item->id }}]"
                                    value="{{ $displayQty }}"
                                    {{ isset($order) && isset($order->state) && ($order->state?->equals(\App\States\Order\Delivered::class) || $order->state?->equals(\App\States\Order\Returned::class) || $order->state?->equals(\App\States\Order\Archived::class)) ? 'readonly' : '' }}
                                    onchange="updateItemHighlighting(this)"
                                    oninput="enforceQuantityLimits(this)"
                                    data-min-qty="0"
                                    data-max-qty="{{ $maxDisplayQty }}">
                            </td>
                            @if(isset($order) && isset($order->state) && ($order->state?->equals(\App\States\Order\Delivered::class) || $order->state?->equals(\App\States\Order\Returned::class) || $order->state?->equals(\App\States\Order\Archived::class)))
                                <td class="text-center">
                                    @if($actualQty > 0)
                                        <input
                                            type="number"
                                            min="0"
                                            max="{{ $displayQty }}"
                                            class="form-control form-control-sm item-qty-input d-inline-block"
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
    <div class="modal-dialog modal-lg modal-dialog-centered modal-fullscreen-sm-down">
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
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.item-carousel-trigger').forEach(el => {
            el.addEventListener('click', function () {
                const images = JSON.parse(this.dataset.images || '[]');
                const itemName = this.dataset.itemName || '';
                showImageCarousel(images, itemName);
            });
        });
    });

    function showImageCarousel(images, itemName) {
        const modalLabel = document.getElementById('imageCarouselModalLabel');
        const carouselInner = document.getElementById('carouselInner');
        const carousel = document.getElementById('itemImageCarousel');

        modalLabel.textContent = `Bilder: ${itemName}`;
        carouselInner.innerHTML = '';

        images.forEach((url, index) => {
            const div = document.createElement('div');
            div.className = `carousel-item ${index === 0 ? 'active' : ''}`;

            const img = document.createElement('img');
            img.src = url;
            img.className = 'd-block w-100';
            img.style.maxHeight = '80vh';
            img.style.objectFit = 'contain';
            img.alt = itemName;

            div.appendChild(img);
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
