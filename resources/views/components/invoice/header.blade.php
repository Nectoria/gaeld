@props(['company', 'invoice'])

<div class="flex justify-between mb-8">
    <!-- Company Information (Left) -->
    <div class="w-1/2">
        <h1 class="text-2xl font-bold mb-2">{{ $company->name }}</h1>
        <div class="text-sm text-gray-700">
            @if($company->street)
                <div>{{ $company->street }}{{ $company->street_number ? ' '.$company->street_number : '' }}</div>
            @endif
            @if($company->postal_code || $company->city)
                <div>{{ $company->postal_code }} {{ $company->city }}</div>
            @endif
            @if($company->country && $company->country !== 'CH')
                <div>{{ strtoupper($company->country) }}</div>
            @endif
        </div>
        <div class="text-sm text-gray-700 mt-2">
            @if($company->phone)
                <div>Tel: {{ $company->phone }}</div>
            @endif
            @if($company->email)
                <div>Email: {{ $company->email }}</div>
            @endif
            @if($company->website)
                <div>{{ $company->website }}</div>
            @endif
        </div>
        @if($company->vat_number)
            <div class="text-sm text-gray-700 mt-2">
                <div>VAT: {{ $company->vat_number }}</div>
            </div>
        @endif
    </div>

    <!-- Invoice Information (Right) -->
    <div class="w-1/3 text-right">
        <h2 class="text-3xl font-bold mb-4">INVOICE</h2>
        <div class="text-sm space-y-1">
            <div><strong>Invoice No:</strong> {{ $invoice->invoice_number }}</div>
            @if($invoice->reference_number)
                <div><strong>Reference:</strong> {{ $invoice->reference_number }}</div>
            @endif
            <div><strong>Date:</strong> {{ $invoice->invoice_date->format('d.m.Y') }}</div>
            <div><strong>Due Date:</strong> {{ $invoice->due_date->format('d.m.Y') }}</div>
            @if($invoice->created_by)
                <div><strong>Created by:</strong> {{ $invoice->creator->name }}</div>
            @endif
        </div>
    </div>
</div>
