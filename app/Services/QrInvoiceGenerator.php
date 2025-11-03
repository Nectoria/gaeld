<?php

namespace App\Services;

use App\Models\Invoice;
use Codedge\Fpdf\Fpdf\Fpdf;
use Exception;
use Illuminate\Support\Facades\Log;
use Sprain\SwissQrBill\DataGroup\Element\AdditionalInformation;
use Sprain\SwissQrBill\DataGroup\Element\CombinedAddress;
use Sprain\SwissQrBill\DataGroup\Element\CreditorInformation;
use Sprain\SwissQrBill\DataGroup\Element\PaymentAmountInformation;
use Sprain\SwissQrBill\DataGroup\Element\PaymentReference;
use Sprain\SwissQrBill\DataGroup\Element\StructuredAddress;
use Sprain\SwissQrBill\PaymentPart\Output\DisplayOptions;
use Sprain\SwissQrBill\PaymentPart\Output\HtmlOutput\HtmlOutput;
use Sprain\SwissQrBill\PaymentPart\Output\FpdfOutput\FpdfOutput;
use Sprain\SwissQrBill\PaymentPart\Translation\Translation;
use Sprain\SwissQrBill\QrBill;
use Sprain\SwissQrBill\QrCode\QrCode;
use Sprain\SwissQrBill\Reference\QrPaymentReferenceGenerator;

class QrInvoiceGenerator
{
    /**
     * Generate a QR invoice PDF for the given invoice
     *
     * @param Invoice $invoice
     * @param string $language Language code (en, de, fr, it)
     * @return string Path to the generated PDF file
     */
    public function generate(Invoice $invoice, string $language = 'en'): string
    {
        $qrBill = $this->createQrBill($invoice);

        // 2. Create a full payment part in HTML
        $output = new HtmlOutput($qrBill, $language);

        // 3. Optional, set layout options
        $displayOptions = new DisplayOptions();
        $displayOptions
            ->setPrintable(false) // true to remove lines for printing on a perforated stationery
            ->setDisplayTextDownArrows(false) // true to show arrows next to separation text, if shown
            ->setDisplayScissors(false) // true to show scissors instead of separation text
            ->setPositionScissorsAtBottom(false) // true to place scissors at the bottom, if shown
        ;

        try {
            $qrBill->getQrCode()->writeFile(__DIR__ . '/qr.png');
            $qrBill->getQrCode()->writeFile(__DIR__ . '/qr.svg');
        } catch (Exception) {
            foreach ($qrBill->getViolations() as $violation) {
                Log::error($violation->getMessage());
            }
            exit;
        }

        // 4. Create a full payment part in HTML
        $html = $output
            ->setDisplayOptions($displayOptions)
            ->getPaymentPart();


        // Save PDF
        $examplePath = __DIR__ . '/html-example.htm';
        file_put_contents($examplePath, $html);

        return $examplePath;
    }

    /**
     * Generate HTML output for preview
     *
     * @param Invoice $invoice
     * @param string $language
     * @return string
     */
    public function generateHtml(Invoice $invoice, string $language = 'en'): string
    {
        $qrBill = $this->createQrBill($invoice, $language);

        $output = new HtmlOutput($qrBill, $language);
        return $output->getHtml();
    }

    /**
     * Create QrBill object from Invoice
     *
     * @param Invoice $invoice
     * @param string $language
     * @return QrBill
     */
    protected function createQrBill(Invoice $invoice): QrBill
    {
        $company = $invoice->company;
        $contact = $invoice->contact;

        // Create QR Bill
        $qrBill = QrBill::create();

        // Creditor (Company)
        $qrBill->setCreditor(
            StructuredAddress::createWithStreet(
                $company->name,
                $company->street,
                $company->street_number,
                $company->postal_code,
                $company->city,
                $company->country ?? 'CH'
            )
        );

        $qrBill->setCreditorInformation(
            CreditorInformation::create(
                $this->formatIban($company->iban)
            )
        );

        // Debtor (Customer)
        if ($contact) {
            $qrBill->setUltimateDebtor(
                StructuredAddress::createWithStreet(
                    $contact->name,
                    $contact->street,
                    $contact->street_number,
                    $contact->postal_code,
                    $contact->city,
                    $contact->country ?? 'CH'
                )
            );
        }

        // Payment amount
        $qrBill->setPaymentAmountInformation(
            PaymentAmountInformation::create(
                $invoice->currency ?? 'CHF',
                $invoice->total_amount / 100 // Convert from cents
            )
        );

        // Payment reference (QR reference if available)
        $referenceNumber = QrPaymentReferenceGenerator::generate(
            '30005',  // You receive this number from your bank (BESR-ID). Unless your bank is PostFinance, in that case use NULL.
            '313947143000901' // A number to match the payment with your internal data, e.g. an invoice number
        );

        $qrBill->setPaymentReference(
            PaymentReference::create(
                PaymentReference::TYPE_QR,
                $referenceNumber
            )
);

        // Additional information
        $additionalInfo = AdditionalInformation::create(
            $this->formatInvoiceMessage($invoice),
            $invoice->notes ?? ''
        );

        $qrBill->setAdditionalInformation($additionalInfo);

        return $qrBill;
    }

    /**
     * Format IBAN (remove spaces)
     *
     * @param string $iban
     * @return string
     */
    protected function formatIban(string $iban): string
    {
        return str_replace(' ', '', $iban);
    }

    /**
     * Format address line
     *
     * @param string|null $street
     * @param string|null $streetNumber
     * @return string
     */
    protected function formatAddress(?string $street, ?string $streetNumber): string
    {
        $parts = array_filter([$street, $streetNumber]);
        return implode(' ', $parts);
    }

    /**
     * Format invoice message for QR bill
     *
     * @param Invoice $invoice
     * @return string
     */
    protected function formatInvoiceMessage(Invoice $invoice): string
    {
        $message = 'Invoice: ' . $invoice->invoice_number;

        if ($invoice->due_date) {
            $message .= ' / Due: ' . $invoice->due_date->format('d.m.Y');
        }

        return $message;
    }
}
