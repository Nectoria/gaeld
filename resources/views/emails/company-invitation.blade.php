<x-mail::message>
# You've Been Invited to Join {{ $companyName }}

Hello!

**{{ $inviterName }}** has invited you to join **{{ $companyName }}** on {{ config('app.name') }}.

You've been invited with the role of **{{ $role }}**, which will give you access to collaborate with the team.

<x-mail::button :url="$acceptUrl">
Accept Invitation
</x-mail::button>

This invitation will expire on **{{ $expiresAt }}**.

If you did not expect this invitation, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
