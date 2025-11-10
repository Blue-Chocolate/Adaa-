@component('mail::message')
# Hello {{ $user->first_name ?? $user->name }},

Please verify your email by clicking the button below.

@component('mail::button', ['url' => $verificationUrl])
Verify email
@endcomponent

This link will expire in 10 minutes.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
