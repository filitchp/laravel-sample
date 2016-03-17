<!DOCTYPE html>
<html>
  <head>
    <title>Laravel 5 Demo</title>

    <link href="//fonts.googleapis.com/css?family=Lato:100,300" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/dropzone.css">
    <link rel="stylesheet" href="css/custom.css">
    
    <script type="text/javascript" src="//code.jquery.com/jquery-2.1.3.min.js"></script>
    <!--<script type="text/javascript" src="js/jquery-2.1.3.min.js"></script>-->
    <script type="text/javascript" src="js/dropzone.js"></script>
    <script type="text/javascript" src="js/bootstrap.min.js"></script>
    <script type="text/javascript" src="js/custom.js"></script>
  </head>
  
  <body>
    <div class="main">
      <div class="content">

        <div class="title">Laravel 5 Demo</div>
        <br>
        <form action="/upload" class="dropzone" id="css-dropzone"></form>

      </div>
    </div>

    {{-- Stats dialog --}}
    <div id="responseModal" class="modal fade" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title" id="responseModalHeader"></h4>
          </div>
          <div class="modal-body" id="responseModalBody"></div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>