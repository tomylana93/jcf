@props(['url'])
@php($mailBrand = resolve(\App\Actions\Settings\GetMailBrand::class)->handle())
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if ($mailBrand['logo_url'])
<img src="{{ $mailBrand['logo_url'] }}" class="logo" style="vertical-align: middle; margin-bottom: 0;" alt="{{ $mailBrand['name'] }}">
@endif
@if ($mailBrand['show_name'])
<span class="brand-name" style="display: inline-block; vertical-align: middle; margin-left: 8px;">{{ $mailBrand['name'] }}</span>
@endif
</a>
</td>
</tr>
