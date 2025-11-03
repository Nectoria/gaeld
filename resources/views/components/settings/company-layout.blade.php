<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <flux:navlist>
            <flux:navlist.item :href="route('company.settings')" :current="request()->routeIs('company.settings')" wire:navigate>{{ __('General') }}</flux:navlist.item>
            @can('manage_company_users')
                <flux:navlist.item :href="route('company.users')" :current="request()->routeIs('company.users')" wire:navigate>{{ __('Team Members') }}</flux:navlist.item>
            @endcan
        </flux:navlist>
    </div>

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <flux:heading>{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

        <div class="mt-5 w-full max-w-3xl">
            {{ $slot }}
        </div>
    </div>
</div>
