<!DOCTYPE html>
<html>
  <head>
    <title>Laravel 5.2 Sample - CSS Stats</title>

    <link href="https://fonts.googleapis.com/css?family=Lato:100,400" rel="stylesheet" type="text/css">

    <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
    
    <style>
      html, body {
        height: 100%;
      }

      body {
        margin: 0;
        padding: 0;
        width: 100%;
        display: table;
        font-weight: 400;
        font-family: 'Lato';
      }

      .container {
        text-align: center;
        display: table-cell;
        vertical-align: middle;
      }

      .content {
        text-align: center;
        display: inline-block;
      }

      .title {
        font-size: 70px;
        font-weight: 100;
      }
      
      p.list_header {
        line-height: 3em;
        font-weight: 400;
      }
      
      .error {
        font-family: 'Arial';
        color: red;
      }
      
      .list-group-item {
        text-align: left;
      }
      
      .badge {
        color: white;
        background-color: #337ab7;
      }
      
    </style>
    
  </head>
  <body>
    <div class="container">
      <div class="content">
        <div class="title">Statistics</div>
        <p class="list-header">File</p>
        <ul class="list-group">
          <li class="list-group-item">
            Lines <span class="badge">{{ $stats['file_stats']['lines'] }}</span>
          </li>
          <li class="list-group-item">
            Characters <span class="badge">{{ $stats['file_stats']['characters'] }}</span>
          </li>
        </ul>
        
        <p class="list-header">CSS</p>
        <ul class="list-group">
          <li class="list-group-item">
            Rulesets <span class="badge">{{ $stats['css_stats']['ruleset_count'] }}</span>
          </li>
          <li class="list-group-item">
            Selectors <span class="badge">{{ $stats['css_stats']['selector_count'] }}</span>
          </li>
          <li class="list-group-item">
            Media queries <span class="badge">{{ $stats['css_stats']['media_query_count'] }}</span>
          </li>
          <li class="list-group-item">
            Use of !important <span class="badge">{{ $stats['css_stats']['important_count'] }}</span>
          </li>
        </ul>
        
        <p class="list-header">Fonts</p>
        <ul class="list-group">
        @foreach($stats['css_stats']['fonts_used'] as $font)
          <li class="list-group-item">
            {{ $font }}
          </li>
        @endforeach
        </ul>
        
        <p class="list-header">Colors</p>
        <ul class="list-group">
        @foreach($stats['css_stats']['colors_used'] as $color)
          <li class="list-group-item" style="background-color: {{$color}}">
            {{ $color }}
          </li>
        @endforeach
        </ul>


      </div>
    </div>
  </body>
</html>