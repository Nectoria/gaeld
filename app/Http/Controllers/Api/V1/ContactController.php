<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Http\Requests\Api\V1\UpdateContactRequest;
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
            'is_active' => 'exact',
            'search' => [
                'type' => 'search',
                'columns' => ['name', 'contact_person', 'email'],
            ],
        ];

        // Apply filters and sorting
        $query = $this->applyQueryModifiers($query, $request, $filters, 'name', 'asc');

        // Paginate
        $perPage = $this->getPaginationParams($request);
        $contacts = $query->paginate($perPage);

        return ContactResource::collection($contacts);
    }

    /**
     * Create a new contact
     */
    public function store(StoreContactRequest $request, Company $company)
    {
        $contact = $this->contactService->createContact($company, $request->validated());

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
    public function update(UpdateContactRequest $request, Company $company, Contact $contact)
    {
        $contact = $this->contactService->updateContact($contact, $request->validated());

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
