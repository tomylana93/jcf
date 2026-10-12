@props([
    'url',
    'color' => 'primary',
    'align' => 'center',
])
@php($accent = resolve(\App\Actions\Settings\GetMailBrand::class)->handle()['accent'])
<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td>
<a href="{{ $url }}" class="button button-{{ $color }}" @if ($color === 'primary') style="background-color: {{ $accent }}; border-color: {{ $accent }};" @endif target="_blank" rel="noopener">{!! $slot !!}</a>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>
