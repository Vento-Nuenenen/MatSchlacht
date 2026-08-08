<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Attachment;
use App\Models\Item;
use App\Models\ItemGroup;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ItemController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Item::class, 'item');
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    private function handleFileUploads(Item $item, array $files): void
    {
        foreach ($files as $file) {
            $path = $file->store('items', 's3');
            if ($path) {
                $item->attachments()->create(['path' => $path]);
            }
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View|Factory
    {
        $query = Item::query();

        if ($request->filled('item_group_id')) {
            $query->where('item_group_id', $request->item_group_id);
        }

        $items = $query->get()->sortBy('item_group_id');
        $itemGroups = ItemGroup::all();

        return view('item.index', [
            'items' => $items,
            'itemGroups' => $itemGroups,
            'currentGroupId' => $request->item_group_id,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Factory|View
    {
        return view('item.create', ['itemGroups' => ItemGroup::all()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreItemRequest $request): RedirectResponse
    {
        $item = Item::create($request->validated());

        if ($request->hasFile('files')) {
            $this->handleFileUploads($item, $request->file('files'));
        }

        return redirect()->route('items.index')->with('success', 'Neues Material wurde erfolgreich erstellt.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Item $item): Factory|View
    {
        $itemGroups = ItemGroup::all();

        return view('item.edit', compact('item', 'itemGroups'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateItemRequest $request, Item $item): RedirectResponse
    {
        $item->update($request->validated());

        if ($request->has('remove_attachments')) {
            /** @var array<int, int> $removeAttachments */
            $removeAttachments = $request->remove_attachments;
            foreach ($removeAttachments as $attachmentId) {
                $attachment = Attachment::find($attachmentId);
                if ($attachment instanceof Attachment) {
                    Storage::disk('s3')->delete($attachment->path);
                    $attachment->delete();
                }
            }
        }

        if ($request->hasFile('files')) {
            $this->handleFileUploads($item, $request->file('files'));
        }

        return redirect()->route('items.index')->with('success', 'Material wurde erfolgreich aktualisiert.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Item $item): RedirectResponse
    {
        $item->delete();

        return redirect()->route('items.index')->with('success', 'Material wurde erfolgreich gelöscht.');
    }
}
