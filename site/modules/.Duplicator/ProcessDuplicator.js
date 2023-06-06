$(document).ready(function() {

    var $job = false;

    $('#newPackage').on('click', function (ev) {
        backupNow($job);
    });

    $('#newPackage_copy').on('click', function (ev) {
        backupNow($job);
    });

    function backupNow(job) {
        if ($job == true) {
            alert('A package build is already running');
            return;
        }

        $.ajax({
                url: $('#newPackage').data('action'),
                context: document.body,
                beforeSend: function () {
                    $job = true;
                    $('#newPackage').addClass("ui-state-active").text('').append($("<i class='fa fa-spinner fa-spin'></i>")).append(' Building ...');
                    $('#newPackage_copy').addClass("ui-state-active").text('').append($("<i class='fa fa-spinner fa-spin'></i>")).append(' Building ...');
                }
            })
            .fail(function (xhr, status, error) {
                $job = false;
                $('#newPackage').removeClass("ui-state-active").text("New package");
                $('#newPackage_copy').removeClass("ui-state-active").text("New package");
                var err = eval("(" + xhr.responseText + ")");
                redirectToDuplicator('An error occured: ' + err.Message, 'error');
            })
            .done(function () {
                $job = false;
                $('#newPackage').text(" New Package").removeClass("ui-state-active");
                $('#newPackage_copy').text(" New Package").removeClass("ui-state-active");
                redirectToDuplicator('', 'none');

            });
    }

    function redirectToDuplicator(str, type) {

        var href = '';
        var encoded = encodeURI(str);

        switch (type) {
            case 'none':
                href = $('#newPackage').data('action').replace('?action=backup_now', '');
                break;
            default:
                href = $('#newPackage').data('action').replace('?action=backup_now', '?action=' + type + '&msg=' + encoded);
                break;
        }

        window.location.replace(href);
    }

});