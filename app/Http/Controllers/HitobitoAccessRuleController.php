<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHitobitoAccessRuleRequest;
use App\Http\Requests\UpdateHitobitoAccessRuleRequest;
use App\Models\HitobitoAccessRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class HitobitoAccessRuleController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(HitobitoAccessRule::class, 'hitobito_access_rule');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $rules = HitobitoAccessRule::all();

        return view('hitobito_access_rules.index', compact('rules'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $roles = Role::all();

        return view('hitobito_access_rules.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreHitobitoAccessRuleRequest $request): RedirectResponse
    {
        HitobitoAccessRule::create($request->validated());

        return redirect()->route('hitobito-access-rules.index')
            ->with('success', 'Regel wurde erstellt.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(HitobitoAccessRule $hitobitoAccessRule): View
    {
        $roles = Role::all();

        return view('hitobito_access_rules.edit', compact('hitobitoAccessRule', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateHitobitoAccessRuleRequest $request, HitobitoAccessRule $hitobitoAccessRule): RedirectResponse
    {
        $hitobitoAccessRule->update($request->validated());

        return redirect()->route('hitobito-access-rules.index')
            ->with('success', 'Regel wurde aktualisiert.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HitobitoAccessRule $hitobitoAccessRule): RedirectResponse
    {
        $hitobitoAccessRule->delete();

        return redirect()->route('hitobito-access-rules.index')
            ->with('success', 'Regel wurde gelöscht.');
    }
}
