Dropzone.options.cssDropzone = {
  paramName: "file", // The name that will be used to transfer the file
  maxFilesize: 2, // MB
  uploadMultiple: false,
  acceptedFiles: "text/*",
  dictDefaultMessage: "Upload a CSS file<br>(Drag a file or click here)",
  success: function(file, responseText, e)
  {
    $('#responseModalHeader').html('Result');
    $('#responseModalBody').html(responseText);
    $('#responseModal').modal('show');
  }
};