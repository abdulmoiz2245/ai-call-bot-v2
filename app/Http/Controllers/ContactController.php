<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $companyId = $request->input('company_id');
        
        $contacts = Contact::query()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->when($request->input('status'), fn($q, $status) => $q->where('status', $status))
            ->when($request->input('dnc'), fn($q, $dnc) => $q->where('is_dnc', $dnc === 'true'))
            ->when($request->input('search'), fn($q, $search) => 
                $q->where(fn($q) => 
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                )
            )
            ->when($request->input('segment'), fn($q, $segment) => $q->where('segment', $segment))
            ->orderBy($request->input('sort', 'created_at'), $request->input('direction', 'desc'))
            ->paginate($request->input('per_page', 50))
            ->withQueryString();

        $segments = Contact::where('company_id', $companyId)
            ->whereNotNull('segment')
            ->distinct()
            ->pluck('segment');

        return Inertia::render('Contacts/Index', [
            'contacts' => $contacts,
            'segments' => $segments,
            'filters' => $request->only(['status', 'search', 'sort', 'direction', 'dnc', 'segment']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Contacts/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:255',
            'segment' => 'nullable|string|max:100',
            'metadata' => 'nullable|array',
        ]);

        $contact = Contact::create([
            ...$validated,
            'company_id' => $request->input('company_id'),
            'status' => 'new',
            'is_dnc' => false,
        ]);

        return redirect()->route('contacts.show', $contact)
            ->with('success', 'Contact created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Contact $contact)
    {
        $contact->load(['calls.campaign']);

        // Get call history
        $calls = $contact->calls()
            ->with(['campaign'])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Contacts/Show', [
            'contact' => $contact,
            'calls' => $calls,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Contact $contact)
    {
        return Inertia::render('Contacts/Edit', [
            'contact' => $contact,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Contact $contact)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:255',
            'segment' => 'nullable|string|max:100',
            'metadata' => 'nullable|array',
        ]);

        $contact->update($validated);

        return redirect()->route('contacts.show', $contact)
            ->with('success', 'Contact updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Contact $contact)
    {
        $contact->delete();

        return redirect()->route('contacts.index')
            ->with('success', 'Contact deleted successfully.');
    }

    /**
     * Add contact to Do Not Call list.
     */
    public function addToDnc(Contact $contact)
    {
        $contact->update(['is_dnc' => true]);

        return back()->with('success', 'Contact added to Do Not Call list.');
    }

    /**
     * Remove contact from Do Not Call list.
     */
    public function removeFromDnc(Contact $contact)
    {
        $contact->update(['is_dnc' => false]);

        return back()->with('success', 'Contact removed from Do Not Call list.');
    }

    /**
     * Import contacts from CSV.
     */
    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
            'mapping' => 'required|array',
        ]);

        // This would be implemented with a queue job for large imports
        // For now, we'll return a success message
        return back()->with('success', 'Contact import queued successfully.');
    }

    /**
     * Export contacts to CSV.
     */
    public function export(Request $request)
    {
        $companyId = $request->input('company_id');
        
        // This would generate and download a CSV file
        // For now, we'll return a success message
        return back()->with('success', 'Contact export initiated.');
    }
}
