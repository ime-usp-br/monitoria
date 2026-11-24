const $ = window.jQuery;

$(function () {
    $('#menulateral').menu();

    $('#btn-dispatchEmails').on('click', function () {
        if ($('#btn-dispatchEmails').val() == 1) {
            $('input:checkbox').prop('checked', false);
            $('#btn-dispatchEmails').val(0);
            $('#btn-dispatchEmails').text(' Marcar Todos');
            $('#btn-dispatchEmails').prepend("<i class='icon-check'></i>");
        } else {
            $('input:checkbox').prop('checked', true);
            $('#btn-dispatchEmails').val(1);
            $('#btn-dispatchEmails').text(' Desmarcar Todos');
            $('#btn-dispatchEmails').prepend("<i class='icon-check-empty'></i>");
        }
    });

    $('#tabela-inscritos').DataTable({
        dom: 'B',
        buttons: {
            buttons: [{
                extend: 'excel',
                text: 'Exportar para Excel',
                className: 'btn-outline-primary',
                exportOptions: {
                    columns: [0, 1, 2, 4, 5, 6, 7, 8, 9],
                    format: {
                        body: function (data) {
                            let newData = data.replace(/<br\s*\/?>/gi, '; ');
                            while (newData.indexOf('  ') !== -1) {
                                newData = newData.replace(/  /g, ' ');
                            }
                            return newData;
                        },
                    },
                },
            }],
            dom: { button: { className: 'btn' } },
        },
        paging: false,
        order: [],
    });

    initMailTemplateFrequency();
    initOldDbProgress();
    initSchoolClassesProgress();

    $(document).on('click', '[data-remove-recommendation]', function (event) {
        event.preventDefault();
        $('#indicacao-' + $(this).data('remove-recommendation')).remove();
    });

    $(document).on('click', '[data-remove-horario]', function (event) {
        event.preventDefault();
        $('#horario-' + $(this).data('remove-horario')).remove();
    });

    $(document).on('click', '[data-remove-instrutor]', function (event) {
        event.preventDefault();
        $('#instrutor-' + $(this).data('remove-instrutor')).remove();
    });

    $(document).on('click', '[data-confirm]', function (event) {
        if (!window.confirm($(this).data('confirm'))) {
            event.preventDefault();
        }
    });

    $('#logout-link').on('click', function (event) {
        event.preventDefault();
        document.getElementById('logout_form2').submit();
    });
});

function initMailTemplateFrequency() {
    const $frequency = $('#sending_frequency');

    if (!$frequency.length) {
        return;
    }

    $frequency.on('change', function () {
        const value = $('#sending_frequency option:selected').val();
        const $dateDiv = $('#date-div');
        const $hourDiv = $('#hour-div');

        $dateDiv.empty();
        $hourDiv.empty();

        if (value == 'Única') {
            $dateDiv.append(
                '<div class="col-12 text-left"><label for="sending_date">Data*:</label></div>' +
                '<div class="col-12"><input class="custom-form-control custom-datepicker" style="max-width:130px" name="sending_date" autocomplete="off"></div>'
            );
            $('.custom-datepicker').datepicker({ showOn: 'both', buttonText: '<i class="far fa-calendar"></i>' });
        } else if (value == 'Mensal') {
            $dateDiv.append(
                '<div class="col-12 text-left"><label for="sending_date">Dia*:</label></div>' +
                '<div class="col-12"><input class="custom-form-control" style="max-width:80px" type="number" min="1" max="31" name="sending_date"></div>'
            );
        } else if (value == 'Inicio do período de avaliação') {
            $dateDiv.append(
                '<div class="col-12 text-left"><label for="sending_date">Dias após o inicio do período*:</label></div>' +
                '<div class="col-12"><input class="custom-form-control" style="max-width:80px" type="number" min="0" name="sending_date" value="0"></div>'
            );
        } else if (value == 'Final do período de avaliação') {
            $dateDiv.append(
                '<div class="col-12 text-left"><label for="sending_date">Dias antes do final do período*:</label></div>' +
                '<div class="col-12"><input class="custom-form-control" style="max-width:80px" type="number" min="0" name="sending_date" value="0"></div>'
            );
        }

        if (value != 'Manual') {
            $hourDiv.append(
                '<div class="col-12 text-left"><label for="sending_hour">Hora*:</label></div>' +
                '<div class="col-12"><input class="custom-form-control" style="max-width:100px" name="sending_hour" type="time"></div>'
            );
        }
    });
}

function initOldDbProgress() {
    if (!$('#btn-importfile').length) {
        return;
    }

    const progress = function () {
        $.ajax({
            url: window.location.origin + '/monitor/getImportOldDBJob',
            dataType: 'json',
            success: function (json) {
                if ('progress' in json) {
                    if (!json.data && !json.failed) {
                        if (document.getElementById('progressbar')) {
                            $('#progressbar').progressbar('value', json.progress);
                        } else if (json.progress != 100) {
                            document.getElementById('btn-importfile').disabled = true;
                            $('#progressbar-div').append("<div id='progressbar'><div class='progress-label'></div></div>");
                            const progressbar = $('#progressbar');
                            const progressLabel = $('.progress-label');
                            progressbar.progressbar({
                                value: false,
                                change: function () {
                                    progressLabel.text(progressbar.progressbar('value') + '%');
                                },
                                complete: function () {
                                    document.getElementById('btn-importfile').disabled = false;
                                    $('#progressbar').remove();
                                    $('#msg-top').empty();
                                    $('#msg-top').append("<p id='success-message' class='alert alert-success'>Os dados foram importados com sucesso.</p>");
                                },
                            });
                        }
                    } else if (json.data && JSON.parse(json.data).status == 'failed' && !document.getElementById('error-message-bottom')) {
                        document.getElementById('btn-importfile').disabled = false;
                        $('#progressbar').remove();
                        const failedLines = JSON.parse(json.data).linhas_com_erros;
                        $('#msg-top').empty();
                        $('#msg-top').append("<p id='error-message' class='alert alert-danger'>Ocorreram alguns erros na importação iniciada em " + json.started_at_exact + '. Linhas do arquivo que não foram possíveis a importação: ' + failedLines + '</p>');
                    } else if (json.failed) {
                        document.getElementById('btn-importfile').disabled = false;
                        $('#progressbar').remove();
                        $('#msg-top').empty();
                        $('#msg-top').append("<p id='error-message' class='alert alert-danger'>Não foi possivel importar os dados iniciada em " + json.started_at_exact + '. Falha critica.</p>');
                    }
                }
                setTimeout(progress, 1000);
            },
        });
    };

    setTimeout(progress, 50);
}

function initSchoolClassesProgress() {
    if (!$('#btn-importModal').length) {
        return;
    }

    const progress = function () {
        $.ajax({
            url: window.location.origin + '/monitor/getimportschoolclassesjob',
            dataType: 'json',
            success: function (json) {
                if ('progress' in json) {
                    if (document.getElementById('progressbar')) {
                        $('#progressbar').progressbar('value', json.progress);
                    } else if (json.progress != 100) {
                        document.getElementById('btn-searchModal').disabled = true;
                        document.getElementById('btn-addModal').disabled = true;
                        document.getElementById('btn-importModal').disabled = true;
                        $('#progressbar-div').append("<div id='progressbar'><div class='progress-label'></div></div>");
                        const progressbar = $('#progressbar');
                        const progressLabel = $('.progress-label');
                        progressbar.progressbar({
                            value: false,
                            change: function () {
                                progressLabel.text(progressbar.progressbar('value') + '%');
                            },
                            complete: function () {
                                location.replace(location);
                            },
                        });
                    }
                }
                setTimeout(progress, 1000);
            },
        });
    };

    setTimeout(progress, 50);
}
