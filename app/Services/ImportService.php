<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Campaign;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use League\Csv\Reader;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportService
{
    /**
     * Detect file type and validate
     */
    public function detectFileType(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (in_array($extension, ['csv', 'txt'])) {
            return 'csv';
        }
        
        if (in_array($extension, ['xlsx', 'xls'])) {
            return 'excel';
        }
        
        throw new \InvalidArgumentException('Unsupported file type. Please upload CSV or Excel files.');
    }

    /**
     * Parse file and return headers for mapping
     */
    public function parseHeaders(UploadedFile $file): array
    {
        $fileType = $this->detectFileType($file);
        
        if ($fileType === 'csv') {
            return $this->parseCsvHeaders($file);
        }
        
        return $this->parseExcelHeaders($file);
    }

    /**
     * Perform dry run validation
     */
    public function dryRun(UploadedFile $file, array $mapping, string $dataType, int $companyId): array
    {
        $fileType = $this->detectFileType($file);
        $rows = $fileType === 'csv' ? $this->parseCsvData($file) : $this->parseExcelData($file);
        
        $validRows = 0;
        $invalidRows = 0;
        $errors = [];
        $sample = [];
        
        foreach ($rows as $index => $row) {
            if ($index === 0) continue; // Skip header
            
            $mappedData = $this->mapRowData($row, $mapping);
            $validation = $this->validateRowData($mappedData, $dataType, $companyId);
            
            if ($validation['valid']) {
                $validRows++;
                if (count($sample) < 5) {
                    $sample[] = $mappedData;
                }
            } else {
                $invalidRows++;
                $errors[] = [
                    'row' => $index + 1,
                    'errors' => $validation['errors']
                ];
            }
            
            // Limit errors to prevent memory issues
            if (count($errors) >= 100) {
                break;
            }
        }
        
        return [
            'total_rows' => count($rows) - 1, // Exclude header
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
            'errors' => array_slice($errors, 0, 10), // Show first 10 errors
            'sample_data' => $sample,
        ];
    }

    /**
     * Import data with mapping
     */
    public function import(UploadedFile $file, array $mapping, string $dataType, int $companyId, int $userId, ?int $campaignId = null): array
    {
        return DB::transaction(function () use ($file, $mapping, $dataType, $companyId, $userId, $campaignId) {
            $fileType = $this->detectFileType($file);
            $rows = $fileType === 'csv' ? $this->parseCsvData($file) : $this->parseExcelData($file);
            
            $imported = 0;
            $skipped = 0;
            $errors = [];
            
            foreach ($rows as $index => $row) {
                if ($index === 0) continue; // Skip header
                
                try {
                    $mappedData = $this->mapRowData($row, $mapping);
                    $validation = $this->validateRowData($mappedData, $dataType, $companyId);
                    
                    if ($validation['valid']) {
                        $this->importRow($mappedData, $dataType, $companyId, $userId, $campaignId);
                        $imported++;
                    } else {
                        $skipped++;
                        $errors[] = [
                            'row' => $index + 1,
                            'errors' => $validation['errors']
                        ];
                    }
                } catch (\Exception $e) {
                    $skipped++;
                    $errors[] = [
                        'row' => $index + 1,
                        'errors' => [$e->getMessage()]
                    ];
                }
            }
            
            return [
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => array_slice($errors, 0, 20), // Show first 20 errors
            ];
        });
    }

    private function parseCsvHeaders(UploadedFile $file): array
    {
        $csv = Reader::createFromPath($file->getPathname(), 'r');
        $csv->setHeaderOffset(0);
        
        return $csv->getHeader();
    }

    private function parseExcelHeaders(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        $headers = [];
        
        $highestColumn = $worksheet->getHighestColumn();
        $columnRange = range('A', $highestColumn);
        
        foreach ($columnRange as $column) {
            $headers[] = $worksheet->getCell($column . '1')->getValue() ?: $column;
        }
        
        return $headers;
    }

    private function parseCsvData(UploadedFile $file): array
    {
        $csv = Reader::createFromPath($file->getPathname(), 'r');
        return iterator_to_array($csv->getRecords());
    }

    private function parseExcelData(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        
        return $worksheet->toArray();
    }

    private function mapRowData(array $row, array $mapping): array
    {
        $mapped = [];
        
        foreach ($mapping as $dbField => $fileColumn) {
            if ($fileColumn !== null && isset($row[$fileColumn])) {
                $mapped[$dbField] = $row[$fileColumn];
            }
        }
        
        return $mapped;
    }

    private function validateRowData(array $data, string $dataType, int $companyId): array
    {
        $rules = $this->getValidationRules($dataType);
        
        $validator = Validator::make($data, $rules);
        
        return [
            'valid' => !$validator->fails(),
            'errors' => $validator->errors()->all(),
        ];
    }

    private function getValidationRules(string $dataType): array
    {
        switch ($dataType) {
            case 'contacts':
                return [
                    'name' => 'required|string|max:255',
                    'phone' => 'required|string|max:20',
                    'email' => 'nullable|email|max:255',
                ];
                
            case 'orders':
                return [
                    'order_number' => 'required|string|max:100',
                    'customer_name' => 'required|string|max:255',
                    'customer_phone' => 'required|string|max:20',
                    'total_amount' => 'required|numeric|min:0',
                ];
                
            case 'leads':
                return [
                    'name' => 'required|string|max:255',
                    'phone' => 'required|string|max:20',
                    'email' => 'nullable|email|max:255',
                    'segment' => 'nullable|string|max:100',
                ];
                
            default:
                return [];
        }
    }

    private function importRow(array $data, string $dataType, int $companyId, int $userId, ?int $campaignId = null): void
    {
        switch ($dataType) {
            case 'contacts':
            case 'leads':
                $this->importContact($data, $companyId, $campaignId, $dataType === 'leads');
                break;
                
            case 'orders':
                $this->importOrder($data, $companyId, $campaignId);
                break;
        }
    }

    private function importContact(array $data, int $companyId, ?int $campaignId, bool $isLead = false): void
    {
        // Check if contact already exists
        $existing = Contact::where('company_id', $companyId)
            ->where('phone', $data['phone'])
            ->first();
            
        if (!$existing) {
            Contact::create([
                'company_id' => $companyId,
                'campaign_id' => $campaignId,
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'segment' => $isLead ? ($data['segment'] ?? 'imported') : null,
                'status' => 'new',
                'is_dnc' => false,
                'tags' => $data['tags'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        }
    }

    private function importOrder(array $data, int $companyId, ?int $campaignId): void
    {
        // Create or find contact
        $contact = Contact::firstOrCreate(
            [
                'company_id' => $companyId,
                'phone' => $data['customer_phone'],
            ],
            [
                'campaign_id' => $campaignId,
                'name' => $data['customer_name'],
                'email' => $data['customer_email'] ?? null,
                'status' => 'new',
                'is_dnc' => false,
            ]
        );

        // Create order
        Order::create([
            'company_id' => $companyId,
            'contact_id' => $contact->id,
            'campaign_id' => $campaignId,
            'order_number' => $data['order_number'],
            'total_amount' => $data['total_amount'],
            'status' => $data['status'] ?? 'pending',
            'order_date' => $data['order_date'] ?? now(),
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Get available mapping fields for data type
     */
    public function getAvailableFields(string $dataType): array
    {
        switch ($dataType) {
            case 'contacts':
                return [
                    'name' => 'Full Name *',
                    'phone' => 'Phone Number *',
                    'email' => 'Email Address',
                    'tags' => 'Tags',
                    'notes' => 'Notes',
                ];
                
            case 'orders':
                return [
                    'order_number' => 'Order Number *',
                    'customer_name' => 'Customer Name *',
                    'customer_phone' => 'Customer Phone *',
                    'customer_email' => 'Customer Email',
                    'total_amount' => 'Total Amount *',
                    'status' => 'Order Status',
                    'order_date' => 'Order Date',
                    'notes' => 'Notes',
                ];
                
            case 'leads':
                return [
                    'name' => 'Full Name *',
                    'phone' => 'Phone Number *',
                    'email' => 'Email Address',
                    'segment' => 'Lead Segment',
                    'tags' => 'Tags',
                    'notes' => 'Notes',
                ];
                
            default:
                return [];
        }
    }
}
