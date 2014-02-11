$(document).ready(function() {
  $('a.confirm').click(function(event) {
    event.preventDefault()
    var url = $(this).attr('href');
    var confirm_box = confirm('Are you sure ?');
    if (confirm_box) {
       window.location = url;
    }
  });
  $('#redis_keys').dataTable({
    "bPaginate": false,
    "bLengthChange": false,
    "bFilter": true,
    "bInfo": false,
    "bAutoWidth": true,
    "aaSorting": [[ 0, "asc" ]]
  });
});