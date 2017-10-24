
function createHistoryDataArray(dataArray){
    dataArray = dataArrayRemoveUnsubmitted(dataArray);
    if(typeof dataArray['current'] === 'undefined'){
        return dataArray;
    }
    if(typeof dataArray['history'] === 'undefined'){
        dataArray['history'] = {};
    }
    $.each(dataArray['current'], function(index, ticket) {
        $.each(ticket, function(index, time) {
            if (typeof time['date'] === 'undefined'
            || typeof time['start'] === 'undefined'
            || time['date'].length === 0
            || time['start'].length === 0 ) {
                return;
            }
            var timestamp = new Date(
                time['date']+' '+time['start']
            ).getTime();
            if(timestamp === 'NaN'
            || timestamp === false){
                return;
            }
            timestamp = findFreeTimeSlot(dataArray['history'],timestamp);
            dataArray['history'][timestamp] = time;
            dataArray['history'][timestamp]['current'] = true;
        });
    });
    return dataArray;
}

function findFreeTimeSlot(historyArray,timestamp){
    var i =  0;
    var loop = true;
    // var timestamp = parseInt(timestamp);
    while (loop === true){
        if(typeof historyArray[timestamp]  === 'undefined'){
            loop = false;
            return timestamp;
        }
        if(i>100){
            throw new Error('too many iterations in findFreeTimeSlot');
        }
        i++;
        timestamp++;
    }
}
function updateHistoryDiv(dataArray){
    if(typeof dataArray['history'] === 'undefined'){
        $('#history').hide();
        return false;
    }
    var html = "";
    $.each(dataArray['history'], function(index, ticket) {
        if(typeof ticket.start === 'undefined'){
            return;
        }

        var date = ticket.date.split(' ');
        var day = new Date(date[0]+' '+date[1]+' '+20+date[2])
            .toLocaleString(window.navigator.language, {weekday: 'long'});
        html += '<tr>'
            +'<td><strong>'+ day+'</strong> '+ ticket.date+'</td>'
            +'<td>'+ticket.start+'</td>'
            +'<td>'+ticket.duration+'</td>'
            +'<td><a href="'+youtrackUrl+'/issue/'+ticket.ticketRef+'" target="_blank" >'+ticket.ticketRef+'</a></td>'
            +'<td>'+ticket.description+'</td>'
            +'<td>'+ticket.type+'</td>'
            +'<td>'+ticket.current+'</td>'
            + '</tr>';
    });
    $('#history .list').html(html);
    $('#history').show();
}

/**
 * remove nodes from the history section of the data array which were not submitted
 * @param dataArray array
 * @returns array
 * @constructor
 */
function dataArrayRemoveUnsubmitted(dataArray){
    if(typeof dataArray['history'] === 'undefined'){
        return dataArray;
    }
    $.each(dataArray['history'], function(index, time) {
        if(typeof dataArray['history'][index]['current'] !== 'undefined'
            && dataArray['history'][index]['current']){
            delete dataArray['history'][index];
        }
    });
    return dataArray;
}

