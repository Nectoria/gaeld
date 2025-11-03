<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm 20mm 105mm 20mm; /* Bottom padding for QR section */
            position: relative;
        }

        /* Header styles */
        h1 {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 8pt;
        }

        h2 {
            font-size: 24pt;
            font-weight: bold;
            margin-bottom: 12pt;
        }

        /* Text styles */
        .text-sm { font-size: 9pt; }
        .text-xs { font-size: 7pt; }
        .text-lg { font-size: 12pt; }
        .font-bold { font-weight: bold; }
        .font-semibold { font-weight: 600; }

        /* Layout */
        .flex {
            display: flex;
        }

        .justify-between {
            justify-content: space-between;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        /* Spacing */
        .mb-2 { margin-bottom: 6pt; }
        .mb-4 { margin-bottom: 12pt; }
        .mb-6 { margin-bottom: 18pt; }
        .mb-8 { margin-bottom: 24pt; }
        .mt-1 { margin-top: 3pt; }
        .mt-2 { margin-top: 6pt; }

        .py-2 { padding-top: 6pt; padding-bottom: 6pt; }
        .py-3 { padding-top: 9pt; padding-bottom: 9pt; }
        .px-2 { padding-left: 6pt; padding-right: 6pt; }
        .pr-4 { padding-right: 12pt; }

        /* Colors */
        .text-gray-600 { color: #666; }
        .text-gray-700 { color: #555; }
        .border-gray-300 { border-color: #ccc; }

        /* Borders */
        .border-t { border-top: 1px solid; }
        .pt-2 { padding-top: 6pt; }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th {
            font-weight: bold;
            padding: 6pt;
        }

        table td {
            padding: 9pt 6pt;
        }

        /* Space utilities */
        .space-y-1 > * + * {
            margin-top: 3pt;
        }

        /* Width utilities */
        .w-full { width: 100%; }
        .w-1-2 { width: 50%; }
        .w-1-3 { width: 33.333%; }

        /* Footer section for payment info and notes */
        .footer-section {
            margin-top: 24pt;
            padding-top: 12pt;
            border-top: 1px solid #e0e0e0;
        }

        /* Payment instructions */
        .payment-info {
            background-color: #f9f9f9;
            padding: 12pt;
            margin-bottom: 18pt;
            border-radius: 4pt;
        }

        /* QR Bill Section (at bottom of page) */
        .qr-bill-section {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 210mm;
            height: 105mm;
            border-top: 1px dashed #000;
        }

        /* Page break control */
        .page-break {
            page-break-after: always;
        }

        .no-break {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    <div class="page">
        <!-- Invoice Header -->
        <x-invoice.header :company="$company" :invoice="$invoice" />

        <!-- Customer Address -->
        <x-invoice.customer-address :contact="$contact" :company="$company" />

        <!-- Invoice Items Table -->
        <x-invoice.items-table :items="$items" :currency="$invoice->currency" />

        <!-- Totals -->
        <x-invoice.totals :totals="$totals" :currency="$invoice->currency" :tax-rate="$invoice->tax_rate" />

        <!-- Payment Information -->
        @if($invoice->terms || $invoice->notes || $invoice->qr_iban)
            <div class="footer-section no-break">
                @if($invoice->qr_iban)
                    <div class="payment-info">
                        <h3 class="font-bold mb-2">Payment Information</h3>
                        <div class="text-sm">
                            <div><strong>Payment Reference:</strong> {{ $invoice->qr_reference }}</div>
                            <div><strong>IBAN:</strong> {{ $invoice->qr_iban }}</div>
                            <div><strong>Amount:</strong> {{ $invoice->currency }} {{ $totals['total_formatted'] }}</div>
                            @if($invoice->due_date)
                                <div><strong>Due Date:</strong> {{ $invoice->due_date->format('d.m.Y') }}</div>
                            @endif
                        </div>
                        <div class="text-sm mt-2 text-gray-600">
                            Please use the QR code at the bottom of this invoice for payment.
                        </div>
                    </div>
                @endif

                @if($invoice->terms)
                    <div class="mb-4">
                        <h4 class="font-semibold mb-2">Terms & Conditions</h4>
                        <div class="text-sm text-gray-700">{{ $invoice->terms }}</div>
                    </div>
                @endif

                @if($invoice->notes)
                    <div class="mb-4">
                        <h4 class="font-semibold mb-2">Notes</h4>
                        <div class="text-sm text-gray-700">{{ $invoice->notes }}</div>
                    </div>
                @endif

                @if($invoice->footer)
                    <div class="text-xs text-gray-600 mt-4 text-center">
                        {{ $invoice->footer }}
                    </div>
                @endif
            </div>
        @endif

        <!-- QR Bill Section (Fixed at bottom) -->
        @if($qrCode)
            <div class="qr-bill-section">
                {!! $qrCode !!}
            </div>
        @endif
    </div>
</body>
</html>
