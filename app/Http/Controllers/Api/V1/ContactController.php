<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ContactResource;
use App\Http\Traits\ApiQueryFilter;
use App\Models\Company;
use App\Models\Contact;
use App\Services\ContactService;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    use ApiQueryFilter;

    public function __construct(
        protected ContactService $contactService
    ) {}

    /**
     * List contacts for a company
     */
    public function index(Request $request, Company $company)
    {
        $query = $company->contacts()->getQuery();

        // Define filterable fields
        $filters = [
            'type' => 'exact',
            'status' => 'exact',
            'search' => [
                'type' => 'search',
                'columns' => ['company_name', 'first_name', 'last_name', 'email'],
            ],
        ];

        // Apply filters and sorting
        $query = $this->applyQueryModifiers($query, $request, $filters, 'company_name', 'asc');

        // Paginate
        $perPage = $this->getPaginationParams($request);
        $contacts = $query->paginate($perPage);

        return ContactResource::collection($contacts);
    }

    /**
     * Create a new contact
     */
    public function store(Request $request, Company $company)
    {
        $validated = $request->validate([
            'type' => 'required|in:customer,vendor,both',
            'status' => 'sometimes|in:active,inactive',
            'company_name' => 'required|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:2',
            'vat_number' => 'nullable|string|max:50',
            'iban' => 'nullable|string|max:34',
            'payment_terms_days' => 'nullable|integer|min:0',
            'currency' => 'nullable|string|max:3',
            'notes' => 'nullable|string',
        ]);

        $contact = $this->contactService->createContact($company, $validated);

        return new ContactResource($contact);
    }

    /**
     * Get a specific contact
     */
    public function show(Company $company, Contact $contact)
    {
        return new ContactResource($contact);
    }

    /**
     * Update a contact
     */
    public function update(Request $request, Company $company, Contact $contact)
    {
        $validated = $request->validate([
            'type' => 'sometimes|in:customer,vendor,both',
            'status' => 'sometimes|in:active,inactive',
            'company_name' => 'sometimes|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'payment_terms_days' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $contact = $this->contactService->updateContact($contact, $validated);

        return new ContactResource($contact);
    }

    /**
     * Delete a contact
     */
    public function destroy(Company $company, Contact $contact)
    {
        $this->contactService->deleteContact($contact);

        return response()->noContent();
    }
}
