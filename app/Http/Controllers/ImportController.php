<?php

namespace App\Http\Controllers;

use App\Services\ImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ImportController extends Controller
{
    public function __construct(
        private ImportService $importService
    ) {}

    /**
     * Show import dialog/page
     */
    public function create(Request $request)
    {
        $dataType = $request->input('data_type', 'contacts');
        $campaignId = $request->input('campaign_id');
        
        $availableFields = $this->importService->getAvailableFields($dataType);
        
        return Inertia::render('Import/Create', [
            'data_type' => $dataType,
            'campaign_id' => $campaignId,
            'available_fields' => $availableFields,
        ]);
    }

    /**
     * Parse file headers for mapping
     */
    public function parseHeaders(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
            'data_type' => 'required|in:contacts,orders,leads',
        ]);

        try {
            $headers = $this->importService->parseHeaders($request->file('file'));
            $availableFields = $this->importService->getAvailableFields($request->input('data_type'));
            
            return response()->json([
                'success' => true,
                'headers' => $headers,
                'available_fields' => $availableFields,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Perform dry run validation
     */
    public function dryRun(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
            'mapping' => 'required|array',
            'data_type' => 'required|in:contacts,orders,leads',
            'company_id' => 'required|exists:companies,id',
        ]);

        try {
            $result = $this->importService->dryRun(
                $request->file('file'),
                $request->input('mapping'),
                $request->input('data_type'),
                $request->input('company_id')
            );
            
            return response()->json([
                'success' => true,
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Import data
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
            'mapping' => 'required|array',
            'data_type' => 'required|in:contacts,orders,leads',
            'company_id' => 'required|exists:companies,id',
            'campaign_id' => 'nullable|exists:campaigns,id',
        ]);

        try {
            $result = $this->importService->import(
                $request->file('file'),
                $request->input('mapping'),
                $request->input('data_type'),
                $request->input('company_id'),
                Auth::id(),
                $request->input('campaign_id')
            );
            
            return response()->json([
                'success' => true,
                'message' => "Import completed: {$result['imported']} records imported, {$result['skipped']} skipped.",
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
