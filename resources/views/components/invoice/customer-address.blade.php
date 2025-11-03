@props(['contact', 'company'])

<div class="mb-8">
    <!-- Sender (small font above recipient) -->
    <div class="text-xs text-gray-600 mb-1" style="font-size: 6pt;">
        {{ $company->name }} · {{ $company->street }}{{ $company->street_number ? ' '.$company->street_number : '' }} · {{ $company->postal_code }} {{ $company->city }}
    </div>

    <!-- Recipient Address -->
    <div class="border-t border-gray-300 pt-2">
        <div class="font-bold">{{ $contact->name }}</div>
        @if($contact->contact_person)
            <div>{{ $contact->contact_person }}</div>
        @endif
        @if($contact->street)
            <div>{{ $contact->street }}{{ $contact->street_number ? ' '.$contact->street_number : '' }}</div>
        @endif
        @if($contact->postal_code || $contact->city)
            <div>{{ $contact->postal_code }} {{ $contact->city }}</div>
        @endif
        @if($contact->country && $contact->country !== 'CH')
            <div>{{ strtoupper($contact->country) }}</div>
        @endif
    </div>
</div>
