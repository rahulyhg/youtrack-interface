$(document).ready(function(){
    
    /**
     * add search results
     */
    $('#ticketSearch').on('click', '.ajaxSubmit', function (){
        $('#ticketSearch .ajaxSubmit').prop('disabled', true);
        $('#ticketSearch .searchResponseMore').prop('disabled', true);
        var form = $(this).closest('form');
        form.submit(function (e) {
           e.preventDefault();
           e.stopImmediatePropagation();
           $('#ticketSearch input, #ticketSearch select').each(function(){
                $(this).attr('lastVal',$(this).val());
            });
            $.ajax({dataType: "json",
                type: form.attr('method'),
                url: form.attr('action'),
                data: form.serialize(),
                success: function(result){
                    var html = '<h3>Results</h3><ul>';
                    var keys = Object.keys(result['tickets']);
                    for (var i = 0, len = keys.length; i < len; i++) {
                        html += '<li><a href="#">play</a><span>'+keys[i]+'</span>: '+result['tickets'][keys[i]]+'</li>';
                    }
                    if(result['partialSet']){
                        html += '<li class="partialSet"><button class="searchResponseMore">more</button></li>';
                    }
                    html += '</ul>';
                    $('#ticketSearch #searchResponse').html(html)
                        .attr('after',100)
                        .accordion({collapsible: true});
                    $('#ticketSearch .ajaxSubmit').prop('disabled', false);
                    $('#ticketSearch .searchResponseMore').prop('disabled', false);    
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log(textStatus, errorThrown);
                }
            });
        });
    });

    /**
     * get more results
     */
    $('#ticketSearch').on('click', '.searchResponseMore', function (){
        $('#ticketSearch .ajaxSubmit').prop('disabled', true);
        $('#ticketSearch .searchResponseMore').prop('disabled', true);
        var form = $(this).closest('form');
        var data = 'query='+$('#ticketSearch .query').attr('lastVal')
                +'&project='+$('#ticketSearch .projectselector').attr('lastVal')
                +'&after='+$('#ticketSearch #searchResponse').attr('after');
        $.ajax({dataType: "json",
            type: form.attr('method'),
            url: form.attr('action'),
            data: data,
            success: function(result){
                $('#ticketSearch #searchResponse .partialSet').remove();
                var html = $('#ticketSearch #searchResponse ul').html();
                var keys = Object.keys(result['tickets']);
                for (var i = 0, len = keys.length; i < len; i++) {
                    html += '<li><a href="#">play</a><span>'+keys[i]+'</span>: '+result['tickets'][keys[i]]+'</li>';
                }
                if(result['partialSet']){
                    html += '<li class="partialSet"><button class="searchResponseMore">more</button></li>';
                }
                $('#ticketSearch #searchResponse ul').html(html);
                if(result['partialSet']){
                    var after = parseInt($('#ticketSearch #searchResponse').attr('after'));
                    after += 100;
                    $('#ticketSearch #searchResponse').attr(
                        'after',
                        after
                    );
                }
                $('#ticketSearch .ajaxSubmit').prop('disabled', false);
                $('#ticketSearch .searchResponseMore').prop('disabled', false);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log(textStatus, errorThrown);
            }
        });
    });

    /**
     * add ticket form on search link click
     */
    $('#ticketSearch').on('click', '#searchResponse ul li a', function (){
        var ticketRef = $(this).siblings( "span").first().html();
        ticketRef = ticketRef.split('-');
        addTicketForm();
        var form = $('.forms form').last();
        var TicketElement = form.find('.projectheader');
        TicketElement.children('.projectselector').val(ticketRef[0]);
        TicketElement.children('.ticketnumber').val(ticketRef[1]);
        updateProject(form)
    });
    
    
});