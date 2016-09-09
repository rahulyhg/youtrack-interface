/**
 * javascript for the adding linking tickets (e.g. depends on) to create by form page
 */
$(document).ready(function(){
    /**
     * add new ticket link input row
     * @param linksDiv {string} div containing links
     */
    function addTicketLink(linksDiv){
        var html = linksDiv.find('.hiddenSingleLink').html();
        linksDiv.append( '<div class="singleLink">'+html+'</div>' );
    }

    /**
     * add the link
     */
    $('#toBeImported').on('click', '.addLinkType', function() {
        var linksDiv = $(this).siblings('.ticketLinks');
        addTicketLink(linksDiv);
    });

    /**
     * remove current ticket link
     */
    $('#toBeImported').on('click', '.deleteLinkType', function() {
        var linksDiv = $(this).closest('.ticketLinks');
        $(this).closest('.singleLink').remove();
        var rowCount = linksDiv.children('.singleLink').length;
        if(rowCount === 0){
            addTicketLink(linksDiv);
        }
    });
    
    /**
     * update the summary and work types drop down
     * @param singleLinksDiv {object} jquery object of the link div
     * @param callback {function} callback function
     */
    function updateProject(singleLinksDiv,callback){
        if (typeof (callback) === "undefined") {
            var callback = function(){};
        }
        var project = $(singleLinksDiv).find('.linkProjectSelector').val().trim();
        var ticketNo = $(singleLinksDiv).find('.linkTicketNumber').val().trim();
        if( project === "" || ticketNo === "" ){
            $(singleLinksDiv).find('.ticketsummary').html('');
            callback();
            return;
        }
        var ticket = project + '-' + ticketNo;
        $.ajax({url: "code/ticketAjax.php?ticket="+ticket,
            dataType: "json",
            success: function(result){
                var linkHthml = result['ticketRef']+' : '+result['summary'];
                $(singleLinksDiv).find('.ticketsummary').html(linkHthml);
                callback();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log(textStatus, errorThrown);
            }
        });
    }
    $('#toBeImported').on('change', '.singleLink .linkProjectSelector', function(){
        updateProject($(this).closest('.singleLink'));
    });
    $('#toBeImported').on('change', '.singleLink .linkTicketNumber', function(){
        updateProject($(this).closest('.singleLink'));
    });

});