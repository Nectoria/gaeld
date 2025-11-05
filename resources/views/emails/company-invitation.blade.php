<x-mail::message>
# {{ __("You've Been Invited to Join :company", ['company' => $companyName]) }}

{{ __('Hello!') }}

{{ __(':inviter has invited you to join :company on :app.', ['inviter' => "**{$inviterName}**", 'company' => "**{$companyName}**", 'app' => config('app.name')]) }}

{{ __("You've been invited with the role of :role, which will give you access to collaborate with the team.", ['role' => "**{$role}**"]) }}

<x-mail::button :url="$acceptUrl">
{{ __('Accept Invitation') }}
</x-mail::button>

{{ __('This invitation will expire on :date.', ['date' => "**{$expiresAt}**"]) }}

{{ __('If you did not expect this invitation, you can safely ignore this email.') }}

{{ __('Thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
