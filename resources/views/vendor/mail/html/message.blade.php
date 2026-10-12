<x-mail::layout>
@php($mailBrand = resolve(\App\Actions\Settings\GetMailBrand::class)->handle())
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ $mailBrand['name'] }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
© {{ date('Y') }} {{ $mailBrand['name'] }}. {{ __('All rights reserved.') }}
@if ($mailBrand['footer_text'])
<br>{{ $mailBrand['footer_text'] }}
@endif
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
