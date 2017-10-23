$(document).ready(function(){
    $('div.roundingDiv select#timeRounding').on('change',function(){
        storeRounding();
    });

    $('.forms').on('change', 'form input, form select', function(){
        var form = $(this).closest('form');
        $(form).find('.buttonsWrapper .timertoggle').each(function () {
            timerToggleButtonUpdate(this);
        });
        storeFormData();
    });

    /**
     * stop enter submitting forms
     */
    $(window).keydown(function(event){
        if(event.keyCode === 13) {
            event.preventDefault();
            return false;
        }
    });

    $('.forms').on('change', 'form table tr td .clockpicker', function(){
        var inputField = $(this).find('input');
        var timeArray = inputField.val().split(":");
        if (timeArray.length > 2) {
            timeArray.pop();
            inputField.val(timeArray.join(":"));
        }
        var timeRow = $(this).closest('tr');
        updateDifference(timeRow);
    });

    $('.forms').on('click', 'form .projectheader .timertoggle', function(){
        timertoggle(this);
    });

    /**
     * add clock face time picker
     */
    $('.forms').on('focus','.clockpicker', function(){
        $(this).clockpicker({
            placement: 'bottom', // clock popover placement
            align: 'left',       // popover arrow align
            donetext: 'Done',     // done button text
            autoclose: true,    // auto close when minute is selected
            vibrate: true        // vibrate the device when dragging clock hand
        });
    });

    /**
     * add date picker
     */
    $('.forms').on('focus','form table tr td input.datepicker', function(){
        $(this).datepicker({
            "dateFormat": 'd M y'
        });
    });

    /**
     * update form when ticket ref changed
     */
    $('.forms').on('change', 'form .projectheader .projectselector', function(){
        var form = $(this).closest('form');
        updateProject(form);
        updateStateFromProjectSelector(form);
    });
    $('.forms').on('change', '.ticketnumber', function(){
        var form = $(this).closest('form');
        var firstLetter  = $(this).val().charAt(0);
        if (firstLetter.length === 1 && firstLetter.match(/[a-zA-Z]/i)) {
            var cuttingPoint = $(this).val().lastIndexOf("-");
            var project = $(this).val().slice(0, cuttingPoint);
            $(form).find('.projectheader .projectselector').val(project);
            var ticketNo = $(this).val().slice(cuttingPoint+1);
            $(this).val(ticketNo);
        }
        updateProject(form);
        updateStateFromProjectSelector(form);
    });


    $('.forms').on('click', 'form .addTimeRow', function(){
        var form = $(this).closest('form');
        addTimeRow(form);
    });

    $('.forms').on('click', '.deleteTimeRow', function() {
        var row = $(this).closest('tr');
        removeTimeRow(row);
        storeFormData()
    });

    /**
     * add a new form for a new ticket
     */
    $('body').on('click','.addTicketForm',function(){
        addTicketForm();
    });

    $('.forms').on('click', '.deleteTimeForm', function() {
        var form = $(this).closest('form');
        removeTicketForm(form);
        storeFormData();
    });

    /**
     * ajax submit & standard submit
     */
    $('.forms').on('click', '.ajaxSubmit', function() {
        $(this).prop('disabled', true);
        var form = $(this).closest('form');
        form.submit(function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            $.ajax({
                type: form.attr('method'),
                url: form.attr('action'),
                data: form.serialize(),
                success: function (rawData) {
                    var data = JSON.parse(rawData);
                    $.each(data, function(index, timeRow) {
                        if(timeRow.success){
                            form.find('table tr td .date[name="'+index+'-date"]')
                                .addClass('submitSuccess')
                                .prop('readonly', true)
                                .removeAttr('name');
                            form.find('table tr td .start[name="'+index+'-start"]')
                                .addClass('submitSuccess')
                                .prop('readonly', true)
                                .removeAttr('name');
                            form.find('table tr td .end[name="'+index+'-end"]')
                                .addClass('submitSuccess')
                                .prop('readonly', true)
                                .removeAttr('name');
                            form.find('table tr td .duration[name="'+index+'-duration"]')
                                .addClass('submitSuccess')
                                .prop('readonly', true)
                                .removeAttr('name');
                            form.find('table tr td .description[name="'+index+'-description"]')
                                .addClass('submitSuccess')
                                .prop('readonly', true)
                                .removeAttr('name');
                            form.find('table tr td .type[name="'+index+'-type"]')
                                .addClass('submitSuccess')
                                .prop('readonly', true)
                                .removeAttr('name');
                        }
                    });
                    console.log(data);
                    // backup old storage
                    var jsonString = localStorage.getItem("json");
                    localStorage.setItem("BACKUP-json", jsonString);
                    // create new json
                    storeFormData();
                    alert("ajax finished: successful submissions are striked through.\n Please note date,duration and type are required for a time to be submitted");
                },
                error: function(jqXHR, textStatus, errorThrown){
                    if(jqXHR['status'] === 401) {
                        window.location.replace("index.php");
                    }else{
                        alert('request failed');
                    }
                }
            });

        });
        $(this).prop('disabled', false);
    });

    $('.forms').on('change', '.stateselector', function(){
        ajaxSubmitState(this);
    });
    populateRounding();
    populateFromJson();

    function onloadUpdateHistoryDiv() {
        var json = localStorage.getItem("json");
        if (typeof json !== 'undefined' && json !== '' && json !== null) {
            var jsonData = JSON.parse(json);
            updateHistoryDiv(jsonData);
        } else {
            $.ajax({
                url: "code/timeJsonGetAjax.php", dataType: "json",
                success: function (result) {
                    updateHistoryDiv(result);
                }
            });
        }
    }
    onloadUpdateHistoryDiv();
});