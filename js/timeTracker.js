$(document).ready(function(){
    // stop enter submitting forms
    $(window).keydown(function(event){
        if(event.keyCode === 13) {
          event.preventDefault();
          return false;
        }
    });
  
    // the difference between two time values
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
    // converts minutes into hours and minutes associative array
    function convertMinutesIntoHours(minutes){
        minutes = parseInt(minutes);
        var hours = Math.floor(minutes / 60);
        var minutesLeft = minutes - (hours * 60);
        return { 'hours':hours , 'minutes':minutesLeft};
    }
    // update the difference field
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

    // adds a 0 if value less than ten
    function timeAddZero(i) {
        if (i < 10) {
            i = "0" + i;
        }
        return i;
    }
    // stop start the timer
    function timertoggle(button){
        var form = $(button).closest('form');
        if($(button).hasClass('play')){
            if($(form).find('table tr:first td input.start').val() !== ''){
               addTimeRow(form);
            }
            var time = new Date($.now());
            var startTime = timeAddZero(time.getHours())+":"+timeAddZero(time.getMinutes());
            $(form).find('table tr:first td input.start')
                .val(startTime);
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

    $('.forms').on('focus','.clockpicker', function(){
        $(this).clockpicker({
            placement: 'bottom', // clock popover placement
            align: 'left',       // popover arrow align
            donetext: 'Done',     // done button text
            autoclose: true,    // auto close when minute is selected
            vibrate: true        // vibrate the device when dragging clock hand
        });
    });
    
    $('.forms').on('focus','form table tr td input.datepicker', function(){
        $(this).datepicker({
          "dateFormat": 'd M, y' 
        });
    });
    
    /*
     * update the summary and work types drop down
     * @param form
     * @param callback - callback function (optional)
     */
    function updateProject(form,callback){
        if (typeof (callback) === "undefined") {
            var callback = function(){};
        }
        var project = $(form).find('.projectheader .projectselector').val();
        var ticketNo = $(form).find('.projectheader .ticketnumber').val();
        if( project === '' || ticketNo === '' ){
            return;
        }
        var ticket = project + '-' + ticketNo;
        $.ajax({url: "src/ticketAjax.php?ticket="+ticket, dataType: "json",
            success: function(result){
                $(form).find('.projectheader .ticketsummary').html(result['summary']);
                var html = '<option value=""></option>';
                for (i = 0; i < result['workTypes'].length; i++){
                    html += '<option value="'+result['workTypes'][i]+'">'+result['workTypes'][i]+'</option>';
                }
                $(form).find('table tr td select.type').html(html);
                callback();
            },
            error: function(result){
            }
        });
    }
    $('.forms').on('change', 'form .projectheader .projectselector', function(){
        var form = $(this).closest('form');
        updateProject(form);
    });
    $('.forms').on('change', '.ticketnumber', function(){
        var form = $(this).closest('form');
        updateProject(form);
    });

    function updateNames(form){
        var nextRowNumber = parseInt( $(form).find('table').attr('nextRowNumber') );
        $(form).find('tr:first td input, tr:first td select').each(function(){
           var name = $(this).attr('name').split('-');
           $(this).attr('name',nextRowNumber + '-' + name[1]);
        });
        $(form).find('table').attr('nextRowNumber',nextRowNumber+1);
    }
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

    function addTicketForm(){
        var html = $('form.template').html();
        $('div.forms').prepend('<form action="src/timeTrackerSubmit.php" method="post" enctype="multipart/form-data">'+html+'</form>');
    }
    $('body').on('click','.addTicketForm',function(){
       addTicketForm();
    });
    
    function createDataArray(){
        var dataArray = {};
        $('.forms form').each(function(){
            // if one or more timeRow not submitted
            if($(this).find('tr [name]').length>0){
                var ticketRef = $(this).find('.projectselector').val() + '-' + $(this).find('.ticketnumber').val();
                dataArray[ticketRef] = {};
                $(this).find('table tr').each(function(n){
                    // if this timeRow not submitted
                    if($(this).find('.date[name]').length>0){
                        dataArray[ticketRef][n] = {};
                        dataArray[ticketRef][n]['date'] = $(this).find('.date[name]').val();
                        dataArray[ticketRef][n]['start'] = $(this).find('.start[name]').val();
                        dataArray[ticketRef][n]['end'] = $(this).find('.end[name]').val();
                        dataArray[ticketRef][n]['duration'] = $(this).find('.duration[name]').val();
                        dataArray[ticketRef][n]['description'] = $(this).find('.description[name]').val();
                        dataArray[ticketRef][n]['type'] = $(this).find('.type[name]').val();
                    }
                });
            }
        });
        return dataArray;
    }
    /*
     * saves form's data into local storage for later retrieval
     */
    function storeFormData(){
        if (typeof (Storage) !== "undefined") {
            var array = createDataArray();
            var jsonString = JSON.stringify(array);
            // Store
            localStorage.setItem("json", jsonString);
        } else {
            alert('local storage feature unavailible with this browser');
        }
    }
    $('.forms').on('change', 'form', function(){
        storeFormData();
    });
   
    /*
     * ajax submit & standard submit disbled
     */
    $('.ajaxSubmit').click(function(){
        var form = $(this).closest('form');
        form.submit(function (ev) {
            ev.preventDefault();
            $.ajax({
                type: form.attr('method'),
                url: form.attr('action'),
                data: form.serialize(),
                success: function (data) {
                    data = JSON.parse(data);
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
                }
            });

        });
    });
    
    /*
     * 
     * e.g of json format required
     * {"test-1":{"0":{"date":"17 Nov, 15","start":"10:10","end":"10:20","duration":"0h 10m","description":"test description","type":"Development"}}}
     */
    function dataIntoForm(){
        var json = localStorage.getItem("json");
        var data = JSON.parse(json);
        var i = 0;
        $.each(data, function(index, ticket) {
            var ticketRef = index.split('-');
            var project = ticketRef[0];
            var ticketNo = ticketRef[1];
            if(i>0){
                addTicketForm();
            }
            var form = $('.forms form:first');
            form.find('.projectheader .projectselector').val(project);
            form.find('.projectheader .ticketnumber').val(ticketNo);
            updateProject(form, function(){
                $.each(ticket, function(index,timeRow) {
                    if(index>0){
                        addTimeRow(form);
                    }
                    form.find('table tr:first td .date').val(timeRow.date);
                    form.find('table tr:first td .start').val(timeRow.start);
                    form.find('table tr:first td .end').val(timeRow.end);
                    form.find('table tr:first td .duration').val(timeRow.duration);
                    form.find('table tr:first td .description').val(timeRow.description);
                    form.find('table tr:first td .type').val(timeRow.type);
                });
            });
            i++;
        });
    }
    dataIntoForm();
    
});