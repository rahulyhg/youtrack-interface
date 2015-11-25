$(document).ready(function(){
    
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
    function updateDifference(form){
            var startTime = $(form).find('table tr:first td input.start').val();
            var endTime = $(form).find('table tr:first td input.end').val();
        
            var durationInMinutes = differenceInMinutes(startTime,endTime);
            var duration = convertMinutesIntoHours(durationInMinutes);
            $(form).find(' table tr:first td input.duration')
                    .val(duration['hours'] +'h '+ duration['minutes']+'m');
    }
    $('form table tr td .clockpicker .form-control').change(function(){
        var form = $(this).parents('form');
        updateDifference(form);
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
        var form = $(button).parent().parent();
        if($(button).hasClass('play')){
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
        
            updateDifference(form);
        }
    }
    $('form .projectheader .timertoggle').click(function(){
        timertoggle(this);
    });

    $('.clockpicker').clockpicker({
        placement: 'bottom', // clock popover placement
        align: 'left',       // popover arrow align
        donetext: 'Done',     // done button text
        autoclose: true,    // auto close when minute is selected
        vibrate: true        // vibrate the device when dragging clock hand
    });
    
    $('form table tr td input.datepicker').datepicker({
      "dateFormat": 'd M, y' 
    });
    
    function updateProject(button){
        var form = $(button).parent().parent();
        var project = $(form).find('.projectheader .projectselector').val();
        var ticketNo = $(form).find('.projectheader .ticketnumber').val();
        var ticket = project + '-' + ticketNo;
        $.ajax({url: "src/ticketAjax.php?ticket="+ticket, dataType: "json",
            success: function(result){
                $(form).find('.projectheader .ticketsummary').html(result['summary']);
                var html = '<option value=""></option>';
                for (i = 0; i < result['workTypes'].length; i++){
                    html += '<option value="'+result['workTypes'][i]+'">'+result['workTypes'][i]+'</option>';
                }
                $(form).find('table tr:first td select.type').html(html);
            },
            error: function(result){
            }
        });
    }
    $('form .projectheader .projectselector').change(function(){
        updateProject(this);
    });
    $('form .projectheader .ticketnumber').change(function(){
        updateProject(this);
    });



});