$(document).ready(function(){
    
    // adds a 0 if value less than ten
    function timeAddZero(i) {
        if (i < 10) {
            i = "0" + i;
        }
        return i;
    }
    // the difference between two hours 
    function hoursDifference(startHour,endHour){
        startHour = parseInt(startHour);
        endHour = parseInt(endHour);
        if(startHour <= endHour){
            return endHour - startHour;
        }else{
            return endHour + 24 - startHour;
        }
    }
    // the difference between two minutes
    function minutesDifference(startMinute,endMinute){
        startMinute = parseInt(startMinute);
        endMinute = parseInt(endMinute);
        if(startMinute <= endMinute){
            return endMinute - startMinute;
        }else{
            return endMinute + 60 - startMinute;
        }
    }
    // the difference between two time values
    function differenceinMinutes(start,end){
        var startTime = start.split(':');
        var endTime = end.split(':');
        var hrsDifference = hoursDifference(startTime[0],endTime[0]);
        if( startTime[0]===endTime[0] && startTime[1] > endTime[1] ){
            hrsDifference += 23;
        }
        var minDifference = minutesDifference(startTime[1],endTime[1]);
        var totalDifferenceInMinutes = ( hrsDifference * 60 ) + minDifference; 
        return totalDifferenceInMinutes;
    }
    // converts minutes into hours and minutes associative array
    function convertTimeIntoHours(minutes){
        minutes = parseInt(minutes);
        var hours = Math.floor(minutes / 60);
        var minutesLeft = minutes - (hours *60);
        return { 'hours':hours , 'minutes':minutesLeft};
    }
    // update the difference field
    function updateDifference(form){
            var startTime = $(form).find('table tr.current td input.start').val();
            var endTime = $(form).find('table tr.current td input.end').val();
        
            var durationInMinutes = differenceinMinutes(startTime,endTime);
            var duration = convertTimeIntoHours(durationInMinutes);
            $(form).find(' table tr.current td input.duration')
                    .val(duration['hours'] +'h '+ duration['minutes']+'m');
    }
    // stop start the timer
    function timertoggle(button){
        var form = $(button).parent().parent();
        if($(button).hasClass('play')){
            var time = new Date($.now());
            var startTime = timeAddZero(time.getHours())+":"+timeAddZero(time.getMinutes());
            $(form).find('table tr.current td input.start')
                .val(startTime);
            $(button).html('stop')
                .removeClass('play')
                .addClass('stop');
        }else if($(button).hasClass('stop')){
            time = new Date($.now());
            var endTime = timeAddZero(time.getHours())+":"+timeAddZero(time.getMinutes());
    
            $(form).find('table tr.current td input.end')
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
    $('form table tr td .clockpicker .form-control').click(function(){
        var form = $(this).parents('form');
        updateDifference(form);
    });

    $('.clockpicker').clockpicker({
        placement: 'bottom', // clock popover placement
        align: 'left',       // popover arrow align
        donetext: 'Done',     // done button text
        autoclose: true,    // auto close when minute is selected
        vibrate: true        // vibrate the device when dragging clock hand
    });

    function updateProject(button){
        var form = $(button).parent().parent();
        var project = $(form).find('.projectheader .projectselector').val();
        var ticketNo = $(form).find('.projectheader .ticketnumber').val();
        var ticket = project + '-' + ticketNo;
        $.ajax({url: "src/ticketAjax.php?ticket="+ticket, dataType: "json",
            success: function(result){
                $(form).find('.projectheader .ticketsummary').html(result);
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