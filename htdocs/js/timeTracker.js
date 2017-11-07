/**
 * add new ticket form, for adding timing for new ticket
 */
function addTicketForm(){
    var form = $('form.template');
    var html = form.html();
    $('div.forms').append('<form action="code/timeTrackerSubmit.php?ajax=true" method="post" enctype="multipart/form-data">'+html+'</form>');
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

/**
 * stop start the timer
 * @param button {string} css selector
 */
function timertoggle(button){
    var form = $(button).closest('form');
    var time = new Date($.now());
    var thisTime = timeAddZero(time.getHours())+":"+timeAddZero(time.getMinutes());
    timerToggleButtonUpdate(button);
    if($(button).hasClass('play')){
        if($(form).find('table tr:first td input.start').val() !== ''){
            addTimeRow(form);
        }
        $(form).find('table tr:first td input.start')
            .val(thisTime);
    } else if($(button).hasClass('stop')){
        $(form).find('table tr:first td input.end')
            .val(thisTime);
        var timeRow = $(form).find('table tr:first');
        updateDifference(timeRow);
    }
    if($(form).find('table tr:first td input.date').val() === '') {
        var date =  $.datepicker.formatDate("d M y", time);
        $(form).find('table tr:first td input.date')
            .val(date);
    }
    timerToggleButtonUpdate(button);
    storeFormData();
}

/**
 * update classes for the timer toggle button
 * @param button
 */
function timerToggleButtonUpdate(button){
    var form = $(button).closest('form');
    if($(form).find('table tr:first td input.start').val() === ''
        || $(form).find('table tr:first td input.end').val() !== ''
    ){
        $(button).html('play')
            .removeClass('stop')
            .addClass('play');
        return;
    }
    $(button).html('stop')
        .removeClass('play')
        .addClass('stop');
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
function storeFormData() {
    var json = localStorage.getItem("json");
    if (typeof json !== 'undefined'
    && json !== ''
    && json !== null) {
        var jsonData = JSON.parse(json);
        storeFormDataCallback(jsonData);
    } else {
        $.ajax({
            url: "code/timeJsonGetAjax.php", dataType: "json",
            success: function (result) {
                storeFormDataCallback(result);
            }
        });
    }
}

function storeFormDataCallback(originalData) {
    var NewDataArray = createDataArray(originalData);
    var jsonString = JSON.stringify(NewDataArray);
    // Store
    if (typeof (Storage) !== "undefined") {
        localStorage.setItem("json", jsonString);
    }
    storeFormDataOnServer(jsonString);
}

function getTimeRowData(timeRow){
    var data = {};
    data['date'] = $(timeRow).find('.date[name]').val();
    data['start'] = $(timeRow).find('.start[name]').val();
    data['end'] = $(timeRow).find('.end[name]').val();
    data['duration'] = $(timeRow).find('.duration[name]').val();
    data['description'] = $(timeRow).find('.description[name]').val();
    data['type'] = $(timeRow).find('.type[name]').val();
    return data;
}

/**
 * create data array of the timing from all forms
 * @returns {{}}
 */
function createDataArray(dataArray){
    dataArray['current'] = {};
    var i = 0;
    $('.forms form').each(function(){
        i++;
        // if one or more timeRow not submitted
        if($(this).find('tr [name]').length>0){
            var ticketRef = $(this).find('.projectselector').val() + '-' + $(this).find('.ticketnumber').val();
            dataArray['current'][i] = {};
            dataArray['current'][i]['ticketRef'] = ticketRef;
            var rows = $(this).find('table tr');
            var noOfTiming = rows.length;
            rows.each(function(n){
                // if this timeRow not submitted
                if( $(this).find('.date[name]').length > 0 ){
                    var j = noOfTiming - n;
                    dataArray['current'][i][j] = getTimeRowData(this);
                    dataArray['current'][i][j]['ticketRef'] = ticketRef;
                } else if($(this).find('.history[value=true]')) {
                    if($(this).hasClass('inHistory')){
                        return;
                    }
                    dataArray = addTimeToHistory(dataArray,time); //----- test me -------
                    $(this).addClass('inHistory');
                }

            });
        }
    });
    dataArray = createHistoryDataArray(dataArray);
    updateHistoryDiv(dataArray);
    return dataArray;
}


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
    if(typeof startTime === 'undefined'
    || typeof endTime === 'undefined'){
        $(timeRow).css("background:red");
        return;
    }
    var durationInMinutes = differenceInMinutes(startTime,endTime);
    durationInMinutes = roundDuration(durationInMinutes);
    durationInMinutes = minDuration(durationInMinutes);
    var duration = convertMinutesIntoHours(durationInMinutes);
    $(timeRow).find('td input.duration')
        .val(duration['hours'] +'h '+ duration['minutes']+'m');
}

/**
 * round the duration up to nearest multiple of #timeRounding value.
 * @param duration
 * @returns {number}
 */
function roundDuration(duration){
    var roundedTo =  $('#timeRounding').val();
    if (!roundedTo) {
        return duration;
    }
    return Math.ceil(duration / roundedTo ) * roundedTo ;
}

/**
 * set to min value if below it.
 * @param duration
 * @returns {number}
 */
function minDuration(duration){
    var minTime =  $('#minDuration').val();
    return  (duration<minTime) ? minTime : duration ;
}

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
    storeFormData();
}

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

/**
 * recover stored form data
 * @param dataArray {array}
 * {"test-1":
     *   {"0":
     *     {"date":"17 Nov, 15","start":"10:10","end":"10:20","duration":"0h 10m","description":"test description","type":"Development"}
     *   }
     *  }
 */
function dataIntoForm(dataArray){
    var i = 0;
    $.each(dataArray['current'], function(index, ticket) {
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
            }
            j++;
        }
        if (form.find('table tr:first td .start').val() && form.find('table tr:first td .end').val() === "") {
            form.find('.timertoggle').html('stop')
                .removeClass('play')
                .addClass('stop');
        }
        updateProject(form,function(){});
        i++;
    });
}

function storeRounding(){
    localStorage.setItem("roundingIncrement", $('div.roundingDiv select#timeRounding').val());
}

function populateRounding(){
    $('div.roundingDiv select#timeRounding').val(localStorage.getItem("roundingIncrement"));
}

function storeMinDuration(){
    localStorage.setItem("minDuration", $('div.minDurationDiv select#minDuration').val());
}

function populateMinDuration(){
    $('div.minDurationDiv select#minDuration').val(localStorage.getItem("minDuration"));
}

/**
 * sets the form content from json from local storage or then from server file
 */
function populateFromJson(){
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
    $( "#wrapper .forms > form:visible" ).each(function( index ) {
        updateStateFromProjectSelector(this);
    });
}

/**
 * @param stateField form element
 */
function ajaxSubmitState(stateField){
    var form = $(stateField).closest('form');
    var sendData = {
        'project': form.find('.projectselector').val(),
        'ticketnumber':  form.find('.ticketnumber').val(),
        'state' : $(stateField).val()
    };
    $.ajax({
        type: form.attr('method'),
        url: form.attr('action'),
        data: sendData,
        success: function (rawData) {
            $(stateField).after("<div class='updateSuccess' >state updated successfully</div>");
            setTimeout( function(){
                $(form).find('.updateSuccess').fadeOut( "slow" );
            }, 3000 );
        },
        error: function (jqXHR, textStatus, errorThrown) {
            if (jqXHR['status'] === 401) {
                window.location.replace("index.php");
            } else {
                alert('request failed');
            }
        }
    })
}

/**
 * update the project row for the new project given
 * @param projectSelector {string} project selector
 * @param form jquery object
 */
function updateStateFromProjectSelector(form){
    var project = $(form).find('.projectselector').val();
    var stateSelector = $(form).find('.stateselector');
    stateSelector.html('<option value="">state...</option>');
    if(project !== ''){
        $.ajax({url: "code/createByFormAjax.php?project="+project, dataType: "json",
            success: function(result){
                stateSelector.html(result['State']['innerHtml']);
            },
            error: function(result){
                console.log('state selector update error');
                console.log(result);
            }
        });
    }
}