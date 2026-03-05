$(document).ready(function() {

  // turn tooltips on
  $('[data-toggle="tooltip"]').tooltip();

  // Zip folder form
  $('#folderFunctionsForm').submit(function(event) {
    event.preventDefault();

    var folderPath = $('#folderNameRadio').val();
    if (!folderPath) return;

    var token = Date.now().toString();

    // Show progress bar at 0%
    $('#zip_message').html(
      '<div class="progress mt-2 mb-1">' +
        '<div class="progress-bar progress-bar-striped progress-bar-animated" ' +
             'id="zip-progress-bar" role="progressbar" ' +
             'style="width:0%;min-width:2em" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>' +
      '</div>' +
      '<small class="text-muted" id="zip-progress-text">Starting&hellip;</small>'
    );

    // Phase 1: start the background zip worker
    $.ajax({
      type:    'POST',
      url:     'json/json-folder_functions.php',
      data:    { folderToZip: folderPath, token: token },
      dataType: 'json'
    }).done(function(data) {
      if (!data.success) {
        $('#zip_message').html(
          '<div class="alert alert-danger mt-2">' + (data.error || 'Failed to start') + '</div>'
        );
        return;
      }

      var count = data.count || 0;
      $('#zip-progress-text').text('Queuing ' + count.toLocaleString() + ' files\u2026');

      // Phase 2: poll progress until done
      var pollInterval = setInterval(function() {
        $.ajax({
          type:    'POST',
          url:     'json/json-progress.php',
          data:    { token: token },
          dataType: 'json'
        }).done(function(p) {
          var pct = p.percent || 0;
          $('#zip-progress-bar')
            .css('width', Math.max(pct, 2) + '%')
            .attr('aria-valuenow', pct)
            .text(pct + '%');

          if (p.finalizing) {
            $('#zip-progress-text').text('Finalizing zip\u2026');
          } else if (p.total > 0) {
            $('#zip-progress-text').text(
              'Queued ' + (p.count || 0).toLocaleString() +
              ' of ' + p.total.toLocaleString() + ' files\u2026'
            );
          }

          if (p.done) {
            clearInterval(pollInterval);

            // Snap bar to green 100%
            $('#zip-progress-bar')
              .css('width', '100%')
              .attr('aria-valuenow', 100)
              .removeClass('progress-bar-animated')
              .addClass('bg-success')
              .text('100%');
            $('#zip-progress-text').text('Done! Starting download\u2026');

            // Trigger download — Content-Length is now known so browser shows progress
            var dlUrl = 'json/json-download.php?token=' + encodeURIComponent(p.token || token) +
                        '&filename=' + encodeURIComponent(p.filename || 'download.zip');
            var a = document.createElement('a');
            a.href = dlUrl;
            a.download = p.filename || 'download.zip';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);

            setTimeout(function() {
              $('#zip_message').html('<div class="alert alert-success mt-2">Download started!</div>');
            }, 1500);
          }
        });
      }, 800);

      // Safety: stop polling after 2 hours
      setTimeout(function() { clearInterval(pollInterval); }, 7200000);

    }).fail(function() {
      $('#zip_message').html(
        '<div class="alert alert-danger mt-2">Request failed. Please try again.</div>'
      );
    });
  });

  // Files-in-folder form (unchanged)
  $('#filesInFolderForm').submit(function(event) {
    event.preventDefault();

    $('.form-error').removeClass('has-error');
    $('.help-block').remove();
    $('.alert-success').remove();

    var dataObj = {};
    var folderName = $('#folder_name').val();
    var folderId   = $('#folder_id').val();

    var fileId          = $("input[name='file_id\\[\\]']").map(function(){ return $(this).val(); }).get();
    var fileURL         = $("input[name='file_url\\[\\]']").map(function(){ return $(this).val(); }).get();
    var fileName        = $("input[name='file_name\\[\\]']").map(function(){ return $(this).val(); }).get();
    var fileTitle       = $("input[name='file_title\\[\\]']").map(function(){ return $(this).val(); }).get();
    var fileDescription = $("input[name='file_description\\[\\]']").map(function(){ return $(this).val(); }).get();

    var folderFilesObj = {};
    $.each(fileId, function(i, val) {
      folderFilesObj[i] = { id: val };
    });
    $.each(fileURL, function(i, val) {
      var url = val.match(/(.*[\/\\])/)[1] || '';
      folderFilesObj[i]['url'] = url + fileName[i];
    });
    $.each(fileName,        function(i, val) { folderFilesObj[i]['name']        = val; });
    $.each(fileTitle,       function(i, val) { folderFilesObj[i]['title']       = val; });
    $.each(fileDescription, function(i, val) { folderFilesObj[i]['description'] = val; });

    dataObj['filesInFolder'] = folderFilesObj;
    dataObj['folderName']    = folderName;
    dataObj['folderId']      = folderId;

    $.ajax({
      type:     'post',
      url:      'json/json-folder_files.php',
      data:     dataObj,
      dataType: 'json',
      encode:   true
    })
    .done(function(data) {
      if (!data.success) {
        if (data.errors) {
          if (data.errors.file_exists) {
            $.each(data.errors.file_exists, function(i, val) {
              $('#filesInFolderForm').prepend('<div class="alert alert-danger">File ' + val + ' already exists.</div>');
            });
          } else {
            $('#filesInFolderForm').prepend('<div class="alert alert-danger">' + data.errors + '</div>');
          }
        }
      } else {
        $('#filesInFolderForm').prepend('<div class="alert alert-success">' + data.message + '</div>');
        $('#filesInFolderForm').append('<div class="alert alert-success">'  + data.message + '</div>');
      }
    })
    .fail(function() {});
  });

});
