// TODO: change this to github
// https://developer.github.com/v3/repos/#create-a-repository-dispatch-event
// /atflick/wp.andyflick.com/dispatches

jQuery(document).ready(
  (function($) {
    // event handler for clicking the link button
    $('#wp-admin-bar-gh-action-dispatch-btn').append('<div class="gh-dispatch-status"></div>');
    checkStatus('');

    $('html')
      $('#wp-admin-bar-gh-action-dispatch-btn a').on('click', function(e) {
        console.log(e, ghActionDispatchOptions);
        if ($(e.currentTarget).hasClass('ab-item')) {
          e.preventDefault();
        }

        updateStatus('queued', null);

        $.ajax({
          type: 'POST',
          url: ghActionDispatchOptions.webhook_url,
          headers: {
            accept: 'application/vnd.github.everest-preview+json',
            'Authorization': 'Bearer ' + ghActionDispatchOptions.auth_key
          },
          contentType: 'application/json',
          dataType: 'json',
          data: JSON.stringify({
            'event_type': ghActionDispatchOptions.event_type
          }),
          success: function() {
            setTimeout(function() {
              checkStatus('');
            },40000);
          }
        });

      });

    function checkStatus(prevStatus) {
      $.ajax({
        type: 'GET',
        url: ghActionDispatchOptions.status_check,
        headers: {
          'Authorization': 'Bearer ' + ghActionDispatchOptions.auth_key
        },
        success: function(d) {
          console.log(d);

          var status = d.workflow_runs[0].status;
          var conclusion = d.workflow_runs[0].conclusion;
          if (status !== prevStatus) {
            updateStatus(status, conclusion);
          }

          if (status === 'in_progress' || status === 'queued') {
            return setTimeout(function () {
              checkStatus(status);
            }, 10000);
          }
        }
      });
    }


    function updateStatus(status, conclusion) {
      var statusClass, icon, text;
      switch (status) {
        case 'queued':
          statusClass = 'dots';
          icon = ghActionDispatchOptions.dots_icon;
          text = 'Queued';
          break;
        case 'in_progress':
          statusClass = 'refresh';
          icon = ghActionDispatchOptions.refresh_icon;
          text = 'Running';
          break;
        case 'completed':
          if (conclusion === 'failure') {
            statusClass = 'cross';
            icon = ghActionDispatchOptions.cross_icon;
            text = 'Failed';
          } else {
            statusClass = 'check';
            icon = ghActionDispatchOptions.check_icon;
            text = 'Success';
          }
          break;

        default:
          statusClass = 'unknown';
          icon = ghActionDispatchOptions.dots_icon;
          text = 'Unknown Status';
          break;
      }
      $('.gh-dispatch-status').html('<span class="gh-dispatch-status-badge -' + statusClass + '"><img src="' + icon + '" alt="Deploy Status" />' + text + '</span>');
    }
  })(jQuery)
);
