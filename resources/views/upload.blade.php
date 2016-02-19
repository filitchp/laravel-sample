<!DOCTYPE html>
<html>
  <head>
    <title>Laravel 5 Code Sample</title>

    <link href="https://fonts.googleapis.com/css?family=Lato:100" rel="stylesheet" type="text/css">

    <style>
      html, body {
        height: 100%;
      }

      body {
        margin: 0;
        padding: 0;
        width: 100%;
        display: table;
        font-weight: 100;
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
        font-size: 60px;
      }
      
      .error {
        font-family: 'Arial';
        color: red;
      }
    </style>
  </head>
  <body>
    <div class="container">
      <div class="content">
        <div class="title">Upload a CSS file</div>
        <br>

        @if(Session::has('error'))
        <p class="error">{!! Session::get('error') !!}</p>
        @endif
        
        @if(!empty($error))
        <p class="error">{!! $error !!}</p>
        @endif

        {!! Form::open(array('url'=>'upload', 'method'=>'POST', 'files'=>true)) !!}

        {!! Form::file('cssfile') !!}
        {!! Form::submit('Submit') !!}
        {!! Form::close() !!}

      </div>
    </div>
  </body>
</html>