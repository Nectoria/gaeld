<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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
    public function store(Request $request, Company $company)
    {
        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'status' => 'sometimes|in:draft,sent,viewed,partial,paid,overdue,cancelled',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'tax_inclusive' => 'sometimes|boolean',
            'currency' => 'sometimes|string|max:3',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'footer' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.unit_price' => 'required|integer|min:0',
            'items.*.tax_rate' => 'required|numeric|min:0|max:100',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $invoice = $this->invoiceService->createInvoice(
            $company,
            $request->user()->id,
            $validated
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
    public function update(Request $request, Company $company, Invoice $invoice)
    {
        // Cannot update paid or cancelled invoices
        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            return response()->json([
                'error' => [
                    'message' => 'Cannot update invoices with status: '.$invoice->status,
                    'code' => 'INVOICE_LOCKED',
                    'status' => 422,
                ],
            ], 422);
        }

        $validated = $request->validate([
            'contact_id' => 'sometimes|exists:contacts,id',
            'invoice_date' => 'sometimes|date',
            'due_date' => 'sometimes|date',
            'notes' => 'nullable|string',
            'items' => 'sometimes|array|min:1',
        ]);

        $invoice = $this->invoiceService->updateInvoice($invoice, $validated);

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
