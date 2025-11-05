<?php

use App\Services\DashboardService;
use Livewire\Volt\Component;

new class extends Component {
    public int $year;
    public array $dashboardData = [];

    public function mount(DashboardService $dashboardService): void
    {
        $this->year = request()->get('year', now()->year);
        $this->loadDashboardData($dashboardService);
    }

    public function loadDashboardData(DashboardService $dashboardService): void
    {
        $companyId = tenant()->currentId();
        $this->dashboardData = $dashboardService->getDashboardData($companyId, $this->year);
    }

    public function changeYear(int $year, DashboardService $dashboardService): void
    {
        $this->year = $year;
        $this->loadDashboardData($dashboardService);
    }

    public function refreshData(DashboardService $dashboardService): void
    {
        $companyId = tenant()->currentId();
        $dashboardService->clearCache($companyId, $this->year);
        $this->loadDashboardData($dashboardService);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Dashboard data refreshed'),
        ]);
    }
}; ?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">{{ __('Dashboard') }}</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Overview of your business performance') }}
            </p>
        </div>

        <div class="flex items-center gap-3">
            {{-- Year Filter --}}
            <flux:select wire:model.live="year" wire:change="changeYear($event.target.value, $wire.$call('loadDashboardData'))">
                @for ($y = now()->year - 5; $y <= now()->year + 1; $y++)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endfor
            </flux:select>

            {{-- Refresh Button --}}
            <flux:button wire:click="refreshData" variant="ghost" icon="arrow-path">
                {{ __('Refresh') }}
            </flux:button>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="mb-4 grid auto-rows-min gap-4 md:grid-cols-3">
        <x-dashboard.stat-card
            :title="__('Total Revenue')"
            :value="money($dashboardData['summary']['total_revenue'] ?? 0, 'CHF')"
            :subtitle="__(':count paid invoices', ['count' => $dashboardData['summary']['paid_invoices'] ?? 0])"
            color="green"
        >
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-dashboard.stat-card>

        <x-dashboard.stat-card
            :title="__('Pending Amount')"
            :value="money($dashboardData['summary']['pending_amount'] ?? 0, 'CHF')"
            :subtitle="__(':count invoices', ['count' => ($dashboardData['summary']['total_invoices'] ?? 0) - ($dashboardData['summary']['paid_invoices'] ?? 0) - ($dashboardData['summary']['draft_invoices'] ?? 0)])"
            color="orange"
        >
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-dashboard.stat-card>

        <x-dashboard.stat-card
            :title="__('Overdue Amount')"
            :value="money($dashboardData['summary']['overdue_amount'] ?? 0, 'CHF')"
            :subtitle="__(':count overdue invoices', ['count' => $dashboardData['summary']['overdue_invoices'] ?? 0])"
            color="red"
        >
            <x-slot:icon>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </x-slot:icon>
        </x-dashboard.stat-card>
    </div>

    {{-- Charts and Other Widgets --}}
    <div class="grid gap-4 md:grid-cols-2">
        {{-- Invoice Timeline Chart --}}
        <x-dashboard.widget :title="__('Invoice Timeline')" class="md:col-span-2">
            <x-slot:icon>
                <svg class="size-5 text-zinc-600 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </x-slot:icon>

            <x-dashboard.invoice-timeline-chart
                :chart-data="$dashboardData['invoiceTimeline'] ?? []"
                :year="$year"
            />
        </x-dashboard.widget>

        {{-- Top Customers --}}
        <x-dashboard.widget :title="__('Top Customers')">
            <x-slot:icon>
                <svg class="size-5 text-zinc-600 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </x-slot:icon>

            @if(count($dashboardData['topCustomers'] ?? []) > 0)
                <div class="space-y-4">
                    @foreach($dashboardData['topCustomers'] as $customer)
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $customer['name'] }}</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-500">
                                    {{ __(':count invoices', ['count' => $customer['invoice_count']]) }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-zinc-900 dark:text-white">
                                    {{ money($customer['total_revenue'], 'CHF') }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <p class="text-sm text-zinc-500 dark:text-zinc-500">{{ __('No customer data available') }}</p>
                </div>
            @endif
        </x-dashboard.widget>

        {{-- Recent Activity --}}
        <x-dashboard.widget :title="__('Recent Activity')">
            <x-slot:icon>
                <svg class="size-5 text-zinc-600 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>

            @if(count($dashboardData['recentActivity'] ?? []) > 0)
                <div class="space-y-3">
                    @foreach($dashboardData['recentActivity'] as $activity)
                        <a
                            href="{{ route('invoices.show', $activity['id']) }}"
                            class="block rounded-lg border border-zinc-200 dark:border-zinc-700 p-3 transition hover:bg-zinc-50 dark:hover:bg-zinc-700/50"
                            wire:navigate
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="font-medium text-zinc-900 dark:text-white">{{ $activity['invoice_number'] }}</p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-500">{{ $activity['customer'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-zinc-900 dark:text-white">
                                        {{ money($activity['amount'], 'CHF') }}
                                    </p>
                                    <flux:badge :variant="match($activity['status']) {
                                        'paid' => 'success',
                                        'overdue' => 'danger',
                                        'draft' => 'ghost',
                                        default => 'warning'
                                    }" size="sm">
                                        {{ __(ucfirst($activity['status'])) }}
                                    </flux:badge>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <p class="text-sm text-zinc-500 dark:text-zinc-500">{{ __('No recent activity') }}</p>
                </div>
            @endif
        </x-dashboard.widget>
    </div>
</div>
