<!DOCTYPE html>
<html>
  <head>
    <title>Laravel 5 Demo</title>

    <link href="//fonts.googleapis.com/css?family=Lato:100,300" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/dropzone.css">

    <style>
      html, body {
        height: 100%;
      }

      body {
        margin: 0;
        padding: 0;
        width: 100%;
        display: table;
        font-weight: 300;
        font-family: 'Lato';
      }

      .main {
        text-align: center;
        display: table-cell;
        vertical-align: middle;
      }

      .content {
        text-align: center;
        display: inline-block;
      }
      
      .dz-message {
        font-size: 18px;
      }

      .title {
        font-size: 60px;
        font-weight: 100;
      }
      
      .error {
        font-family: 'Arial';
        color: red;
      }
    </style>
    
    <script type="text/javascript" src="//code.jquery.com/jquery-2.1.3.min.js"></script>
    <script type="text/javascript" src="js/dropzone.js"></script>
    <script type="text/javascript" src="js/bootstrap.min.js"></script>
    
    <script>
      Dropzone.options.cssDropzone = {
        paramName: "file", // The name that will be used to transfer the file
        maxFilesize: 2, // MB
        uploadMultiple: false,
        acceptedFiles: "text/*",
        dictDefaultMessage: "Upload a CSS file<br>(Drag a file or click here)",
        accept: function(file, done)
        {
          done(); 
        },
        success: function(file, responseText, e)
        {
          $('#responseModalHeader').html('CSS Stats');
          $('#responseModalBody').html(responseText);
          $('#responseModal').modal('show');
        }
      };

    </script>
  </head>
  
  <body>
    <div class="main">
      <div class="content">

        <div class="title">Laravel 5 Demo</div>
        <br>
        <form action="/upload" class="dropzone" id="css-dropzone"></form>

      </div>
    </div>

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