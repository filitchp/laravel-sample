
<p class="list-header"><b>File</b></p>
<ul class="list-group">
  <li class="list-group-item">
    Lines <span class="badge">{{ $stats['file_stats']['lines'] }}</span>
  </li>
  <li class="list-group-item">
    Characters <span class="badge">{{ $stats['file_stats']['characters'] }}</span>
  </li>
</ul>

<p class="list-header"><b>Syntax</b></p>
<ul class="list-group">
  <li class="list-group-item">
    Rulesets <span class="badge">{{ $stats['css_stats']['ruleset_count'] }}</span>
  </li>
  <li class="list-group-item">
    Selectors <span class="badge">{{ $stats['css_stats']['selector_count'] }}</span>
  </li>
  <li class="list-group-item">
    Media query count <span class="badge">{{ $stats['css_stats']['media_query_count'] }}</span>
  </li>
  <li class="list-group-item">
    Media query rulesets <span class="badge">{{ $stats['css_stats']['media_query_rulesets'] }}</span>
  </li>
  <li class="list-group-item">
    Use of !important <span class="badge">{{ $stats['css_stats']['important_count'] }}</span>
  </li>
</ul>

<p class="list-header"><b>Fonts</b></p>
<ul class="list-group">
@foreach($stats['css_stats']['fonts_used'] as $font)
  <li class="list-group-item">
    {{ $font }}
  </li>
@endforeach
</ul>

<p class="list-header"><b>Colors</b></p>
<ul class="list-group">
@foreach($stats['css_stats']['colors_used'] as $color)
  <li class="list-group-item" style="background-color: {{$color}}">
    {{ $color }}
  </li>
@endforeach
</ul>
