<x-mail::message>
You have been requested to provide feedback.

Click on the button below and fill the form:

<x-mail::button :url="$feedback">
{{ __('Send Feedback') }}
</x-mail::button>

{{ __('If you did not expect to receive an invitation to this team, you may discard this email.') }}
</x-mail::message>