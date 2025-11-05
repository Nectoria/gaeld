<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Get dashboard data for a company with caching
     */
    public function getDashboardData(int $companyId, ?int $year = null): array
    {
        $year = $year ?? now()->year;
        $cacheKey = "dashboard.company.{$companyId}.year.{$year}";

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($companyId, $year) {
            return [
                'summary' => $this->getSummaryStats($companyId, $year),
                'invoiceTimeline' => $this->getInvoiceTimeline($companyId, $year),
                'recentActivity' => $this->getRecentActivity($companyId),
                'topCustomers' => $this->getTopCustomers($companyId, $year),
            ];
        });
    }

    /**
     * Get summary statistics
     */
    private function getSummaryStats(int $companyId, int $year): array
    {
        $startDate = Carbon::createFromDate($year, 1, 1)->startOfDay();
        $endDate = Carbon::createFromDate($year, 12, 31)->endOfDay();

        $invoices = Invoice::where('company_id', $companyId)
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->get();

        $totalRevenue = $invoices->where('status', 'paid')->sum('total_amount');
        $pendingAmount = $invoices->whereIn('status', ['sent', 'viewed', 'partial'])->sum('total_amount');
        $overdueAmount = $invoices->where('status', 'overdue')->sum('total_amount');

        return [
            'total_revenue' => $totalRevenue,
            'pending_amount' => $pendingAmount,
            'overdue_amount' => $overdueAmount,
            'total_invoices' => $invoices->count(),
            'paid_invoices' => $invoices->where('status', 'paid')->count(),
            'overdue_invoices' => $invoices->where('status', 'overdue')->count(),
            'draft_invoices' => $invoices->where('status', 'draft')->count(),
        ];
    }

    /**
     * Get invoice timeline data for chart
     * Groups invoices by month and status
     */
    private function getInvoiceTimeline(int $companyId, int $year): array
    {
        $startDate = Carbon::createFromDate($year, 1, 1)->startOfDay();
        $endDate = Carbon::createFromDate($year, 12, 31)->endOfDay();

        // Get invoices grouped by month
        $invoices = Invoice::where('company_id', $companyId)
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->get()
            ->groupBy(function ($invoice) {
                return $invoice->invoice_date->format('Y-m');
            });

        $months = [];
        $paidData = [];
        $dueData = [];
        $expenseData = []; // Future: expenses from vendors

        // Generate all months for the year
        for ($month = 1; $month <= 12; $month++) {
            $monthKey = Carbon::createFromDate($year, $month, 1)->format('Y-m');
            $months[] = Carbon::createFromDate($year, $month, 1)->format('M');

            $monthInvoices = $invoices->get($monthKey, collect());

            // Paid invoices (using paid_at date if available, otherwise invoice_date)
            $paidData[] = $monthInvoices
                ->where('status', 'paid')
                ->sum('total_amount') / 100; // Convert cents to currency units

            // Due/Pending invoices
            $dueData[] = $monthInvoices
                ->whereIn('status', ['sent', 'viewed', 'partial', 'overdue'])
                ->sum('total_amount') / 100;

            // Mock future expenses (TODO: Replace with actual expense data)
            $expenseData[] = 0;
        }

        return [
            'labels' => $months,
            'datasets' => [
                [
                    'label' => 'Paid',
                    'data' => $paidData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.8)', // green
                    'borderColor' => 'rgba(34, 197, 94, 1)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Due/Pending',
                    'data' => $dueData,
                    'backgroundColor' => 'rgba(249, 115, 22, 0.8)', // orange
                    'borderColor' => 'rgba(249, 115, 22, 1)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Expenses',
                    'data' => $expenseData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.8)', // red
                    'borderColor' => 'rgba(239, 68, 68, 1)',
                    'borderWidth' => 1,
                ],
            ],
        ];
    }

    /**
     * Get recent activity (invoices created/updated recently)
     */
    private function getRecentActivity(int $companyId, int $limit = 5): array
    {
        return Invoice::where('company_id', $companyId)
            ->with(['contact'])
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'customer' => $invoice->contact->company_name,
                    'amount' => $invoice->total_amount,
                    'status' => $invoice->status,
                    'date' => $invoice->updated_at,
                ];
            })
            ->toArray();
    }

    /**
     * Get top customers by revenue
     */
    private function getTopCustomers(int $companyId, int $year, int $limit = 5): array
    {
        $startDate = Carbon::createFromDate($year, 1, 1)->startOfDay();
        $endDate = Carbon::createFromDate($year, 12, 31)->endOfDay();

        return Invoice::where('invoices.company_id', $companyId)
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->where('status', 'paid')
            ->join('contacts', 'invoices.contact_id', '=', 'contacts.id')
            ->select(
                'contacts.id',
                'contacts.company_name',
                DB::raw('COUNT(invoices.id) as invoice_count'),
                DB::raw('SUM(invoices.total_amount) as total_revenue')
            )
            ->groupBy('contacts.id', 'contacts.company_name')
            ->orderBy('total_revenue', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->company_name,
                    'invoice_count' => $customer->invoice_count,
                    'total_revenue' => $customer->total_revenue,
                ];
            })
            ->toArray();
    }

    /**
     * Clear dashboard cache for a company
     */
    public function clearCache(int $companyId, ?int $year = null): void
    {
        if ($year) {
            $cacheKey = "dashboard.company.{$companyId}.year.{$year}";
            Cache::forget($cacheKey);
        } else {
            // Clear all years for this company
            $currentYear = now()->year;
            for ($y = $currentYear - 5; $y <= $currentYear + 1; $y++) {
                $cacheKey = "dashboard.company.{$companyId}.year.{$y}";
                Cache::forget($cacheKey);
            }
        }
    }
}
