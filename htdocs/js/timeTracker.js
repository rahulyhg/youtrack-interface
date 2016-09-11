/**
 * add new ticket form, for adding timing for new ticket
 */
function addTicketForm(){
    var form = $('form.template');
    var html = form.html();
    $('div.forms').append('<form action="code/timeTrackerSubmit.php" method="post" enctype="multipart/form-data">'+html+'</form>');
    updateProject(form);
}

/**
 * update the summary and work types drop down
 * @param form {sting} css selector for the form
 * @param callback {function} callback function
 */
function updateProject(form,callback){
    if (typeof (callback) === "undefined") {
        var callback = function(){};
    }
    var project = $(form).find('.projectheader .projectselector').val().trim();
    var ticketNo = $(form).find('.projectheader .ticketnumber').val().trim();
    if( project === "" || ticketNo === "" ){
        $(form).find('.projectheader .ticketsummary').html('');
        callback();
        return;
    }
    var ticket = project + '-' + ticketNo;
    $.ajax({url: "code/ticketAjax.php?ticket="+ticket,
        dataType: "json",
        success: function(result){
            if(result['summary']){
                var linkHthml = '<a href="'+result['ticketUrl']+'" target="_blank" >'+result['ticketRef']+' : '+result['summary']+'</a>';
            }else{
                var linkHthml = 'ticket not found';
            }
            $(form).find('.projectheader .ticketsummary').html(linkHthml);
            var html = '<option value="">type...</option>';
            for (i = 0; i < result['workTypes'].length; i++){
                html += '<option value="'+result['workTypes'][i]+'">'+result['workTypes'][i]+'</option>';
            }
            $(form).find('table tr td select.type').html(html);
            callback();
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log(textStatus, errorThrown);
        }
    });
}

$(document).ready(function(){
    /**
     * stop enter submitting forms
     */
    $(window).keydown(function(event){
        if(event.keyCode === 13) {
          event.preventDefault();
          return false;
        }
    });

    /**
     * the difference between two time values on the same day
     * @param start {string} 24h start time e.g. 12:00
     * @param end {string} 24h end time e.g. 12:00
     * @returns {number} difference in minutes
     */
    function differenceInMinutes(start,end){
        var startTime = start.split(':');
        var endTime = end.split(':');
        
        var startTimeInMin = ( parseInt(startTime[0]) * 60 ) + parseInt(startTime[1]);
        var endTimeInMin = ( parseInt(endTime[0]) * 60 ) + parseInt(endTime[1]);

        if(startTimeInMin<=endTimeInMin) {
            var totalDifferenceInMinutes = endTimeInMin - startTimeInMin;
        }else{
            var minutesInADay = 24 * 60 ;
            var totalDifferenceInMinutes = endTimeInMin + (minutesInADay - startTimeInMin);
        }

        return totalDifferenceInMinutes;
    }
    /**
     * converts minutes into hours and minutes associative array
     * @param minutes {int}
     * @returns {{hours: number, minutes: number}}
     */
    function convertMinutesIntoHours(minutes){
        minutes = parseInt(minutes);
        var hours = Math.floor(minutes / 60);
        var minutesLeft = minutes - (hours * 60);
        return { 'hours':hours , 'minutes':minutesLeft};
    }
    /**
     * update the difference field
     * @param timeRow {string} css selector
     */
    function updateDifference(timeRow){
        var startTime = $(timeRow).find('td input.start').val();
        var endTime = $(timeRow).find('td input.end').val();

        var durationInMinutes = differenceInMinutes(startTime,endTime);
        var duration = convertMinutesIntoHours(durationInMinutes);
        $(timeRow).find('td input.duration')
                    .val(duration['hours'] +'h '+ duration['minutes']+'m');
    }
    $('.forms').on('change', 'form table tr td .clockpicker', function(){
        var timeRow = $(this).closest('tr');
        updateDifference(timeRow);
    });

    /**
     * adds a 0 to start of string if value less than ten
     * @param i {int}
     * @returns {int}
     */
    function timeAddZero(i) {
        if (i < 10) {
            i = "0" + i;
        }
        return i;
    }

    /**
     * stop start the timer
     * @param button {string} css selector
     */
    function timertoggle(button){
        var form = $(button).closest('form');
        if($(button).hasClass('play')){
            if($(form).find('table tr:first td input.start').val() !== ''){
               addTimeRow(form);
            }
            var time = new Date($.now());
            var startTime = timeAddZero(time.getHours())+":"+timeAddZero(time.getMinutes());
            var date =  $.datepicker.formatDate("d M y", time);
            $(form).find('table tr:first td input.start')
                .val(startTime);
            $(form).find('table tr:first td input.date')
                .val(date);
            $(button).html('stop')
                .removeClass('play')
                .addClass('stop');
        }else if($(button).hasClass('stop')){
            time = new Date($.now());
            var endTime = timeAddZero(time.getHours())+":"+timeAddZero(time.getMinutes());
    
            $(form).find('table tr:first td input.end')
                .val(endTime);
            $(button).html('play')
                .removeClass('stop')
                .addClass('play');
        
            var timeRow = $(form).find('table tr:first');
            updateDifference(timeRow);
        }
        storeFormData();
    }
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
    });
    $('.forms').on('change', '.ticketnumber', function(){
        var form = $(this).closest('form');
        updateProject(form);
    });

    /**
     * update row number in field names
     * @param form {string} css selector of the form
     */
    function updateNames(form){
        var nextRowNumber = parseInt( $(form).find('table').attr('nextRowNumber') );
        $(form).find('tr:first td input, tr:first td select').each(function(){
           var name = $(this).attr('name').split('-');
           $(this).attr('name',nextRowNumber + '-' + name[1]);
        });
        $(form).find('table').attr('nextRowNumber',nextRowNumber+1);
    }
    /**
     * add a time row to ticket form
     * @param form {string} css selector of the form
     */
    function addTimeRow(form){
        var html = $('form.template').find('table tr:first').html();
        $(form).find('table tbody').prepend('<tr>'+html+'</tr>');
        html = $(form).find('table tr:last td select.type').html();
        $(form).find('table tr:first td select.type').html(html);
        updateNames(form);
    }
    $('.forms').on('click', 'form .addTimeRow', function(){
        var form = $(this).closest('form');
        addTimeRow(form);
    });

    /**
     * remove time row from ticket form
     * @param row {string} css selector
     */
    function removeTimeRow(row){
        var tbody = $(row).parent();
        $(row).remove();
        var rowCount = $(tbody).children('tr').length;
        var form = $(tbody).closest('form');
        if(rowCount === 0){
            addTimeRow(form);
        }
    }
    $('.forms').on('click', '.deleteTimeRow', function() {
        var row = $(this).closest('tr');
        removeTimeRow(row);
    });

    /**
     * add a new form for a new ticket
     */
    $('body').on('click','.addTicketForm',function(){
       addTicketForm();
    });

    /**
     * remove ticket form
     * @param form {string} css selector of the form
     */
    function removeTicketForm(form){
        $(form).remove();
        var formCount = $('.forms').children('form').length;
        if(formCount === 0){
            addTicketForm();
        }
    }
    $('.forms').on('click', '.deleteTimeForm', function() {
        var form = $(this).closest('form');
        removeTicketForm(form);
    });

    /**
     * create data array of the timing from all forms
     * @returns {{}}
     */
    function createDataArray(){
        var dataArray = {};
        var i = 0;
        $('.forms form').each(function(){
            i++;
            // if one or more timeRow not submitted
            if($(this).find('tr [name]').length>0){
                var ticketRef = $(this).find('.projectselector').val() + '-' + $(this).find('.ticketnumber').val();
                dataArray[i] = {};
                dataArray[i]['ticketRef'] = ticketRef;
                $(this).find('table tr').each(function(n){
                    var noOfTiming = $(this).find('.date[name]').length;
                    // if this timeRow not submitted
                    if(noOfTiming>0){
                        dataArray[i][noOfTiming-n] = {};
                        dataArray[i][noOfTiming-n]['date'] = $(this).find('.date[name]').val();
                        dataArray[i][noOfTiming-n]['start'] = $(this).find('.start[name]').val();
                        dataArray[i][noOfTiming-n]['end'] = $(this).find('.end[name]').val();
                        dataArray[i][noOfTiming-n]['duration'] = $(this).find('.duration[name]').val();
                        dataArray[i][noOfTiming-n]['description'] = $(this).find('.description[name]').val();
                        dataArray[i][noOfTiming-n]['type'] = $(this).find('.type[name]').val();
                    }
                });
            }
        });
        return dataArray;
    }
    /**
     * saves form's data onto server for later retrieval
     * @param jsonString {string}
     */
    function storeFormDataOnServer(jsonString){
        $.ajax({url: "code/timeJsonSaveAjax.php",
            type: 'POST',
            dataType: "json",
            data: { json: jsonString },
            success: function(result){
                return result;
            }
        });
    }
    /**
     * saves form's data into local storage for later retrieval
     */
    function storeFormData(){
        var array = createDataArray();
        var jsonString = JSON.stringify(array);
        // Store
        if (typeof (Storage) !== "undefined") {
            localStorage.setItem("json", jsonString);
        }
        storeFormDataOnServer(jsonString);
    }
    $('.forms').on('change', 'form', function(){
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
                    var jsonString = localStorage.getItem("json", jsonString);
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
    
    
    /**
     * recover stored form data
     * @param data {array}
     * {"test-1":
     *   {"0":
     *     {"date":"17 Nov, 15","start":"10:10","end":"10:20","duration":"0h 10m","description":"test description","type":"Development"}
     *   }
     *  }
    */
    function dataIntoForm(data){
        var i = 0;
        $.each(data, function(index, ticket) {
            var ticketRef = ticket.ticketRef.split('-');
            delete ticket.ticketRef; 
            var project = ticketRef[0];
            var ticketNo = ticketRef[1];
            // if not first ticket
            if(i>0){
                addTicketForm();
            }
            var form = $('.forms form:last');
            form.find('.projectheader .projectselector').val(project);
            form.find('.projectheader .ticketnumber').val(ticketNo);
            var j = 0;
            for (var key in ticket) {
                if( ticket[key] !== undefined ) {
                    var timeRow = ticket[key];
                    if( j > 0 ){
                        addTimeRow(form);
                    }
                    form.find('table tr:first td .date').val(timeRow.date);
                    form.find('table tr:first td .start').val(timeRow.start);
                    form.find('table tr:first td .end').val(timeRow.end);
                    form.find('table tr:first td .duration').val(timeRow.duration);
                    form.find('table tr:first td .description').val(timeRow.description);
                    form.find('table tr:first td .type').val(timeRow.type);
                };
                j++;
            }
            updateProject(form);
            i++;
        });
    }
    
    /**
     * sets the form content from json from local storage or then from server file
     */
    function populateFormJson(){
        var json = localStorage.getItem("json");
        if (typeof json !== 'undefined' && json !== '' && json !== null ) {
            var jsonData = JSON.parse(json);
            dataIntoForm(jsonData);
        } else {
            $.ajax({url: "code/timeJsonGetAjax.php", dataType: "json",
                success: function(result){
                    dataIntoForm(result);
                }
            });
        }
    }
    populateFormJson();
});