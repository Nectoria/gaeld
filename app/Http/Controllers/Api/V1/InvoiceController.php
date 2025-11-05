<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreInvoiceRequest;
use App\Http\Requests\Api\V1\UpdateInvoiceRequest;
use App\Http\Resources\Api\V1\InvoiceResource;
use App\Http\Traits\ApiQueryFilter;
use App\Models\Company;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    use ApiQueryFilter;

    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    /**
     * List invoices for a company
     */
    public function index(Request $request, Company $company)
    {
        $query = $company->invoices()->with(['contact', 'items'])->getQuery();

        // Define filterable fields
        $filters = [
            'status' => 'exact',
            'contact_id' => 'exact',
            'from_date' => ['type' => 'date_from', 'column' => 'invoice_date'],
            'to_date' => ['type' => 'date_to', 'column' => 'invoice_date'],
        ];

        // Apply filters and sorting
        $query = $this->applyQueryModifiers($query, $request, $filters, 'invoice_date', 'desc');

        // Paginate
        $perPage = $this->getPaginationParams($request);
        $invoices = $query->paginate($perPage);

        return InvoiceResource::collection($invoices);
    }

    /**
     * Create a new invoice
     */
    public function store(StoreInvoiceRequest $request, Company $company)
    {
        $invoice = $this->invoiceService->createInvoice(
            $company,
            $request->user()->id,
            $request->validated()
        );

        return new InvoiceResource($invoice->load(['contact', 'items']));
    }

    /**
     * Get a specific invoice
     */
    public function show(Company $company, Invoice $invoice)
    {
        return new InvoiceResource($invoice->load(['contact', 'items']));
    }

    /**
     * Update an invoice
     */
    public function update(UpdateInvoiceRequest $request, Company $company, Invoice $invoice)
    {
        $invoice = $this->invoiceService->updateInvoice($invoice, $request->validated());

        return new InvoiceResource($invoice->load(['contact', 'items']));
    }

    /**
     * Delete an invoice
     */
    public function destroy(Company $company, Invoice $invoice)
    {
        // Cannot delete paid invoices
        if ($invoice->status === 'paid') {
            return response()->json([
                'error' => [
                    'message' => 'Cannot delete paid invoices',
                    'code' => 'INVOICE_LOCKED',
                    'status' => 422,
                ],
            ], 422);
        }

        $invoice->delete();

        return response()->noContent();
    }
}
