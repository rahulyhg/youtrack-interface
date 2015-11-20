$(document).ready(function(){
    function timertoggle(button){
        var form = $(button).parent().parent();
        if($(button).hasClass('play')){
            $(form).find('table tr.current td input.start')
                .val(new Date($.now()));
            $(button).html('stop')
                .removeClass('play')
                .addClass('stop');
        }else if($(button).hasClass('stop')){
            var startTime = new Date($(form).find(' table tr.current td input.start'));
            var currentTime = new Date($.now());
            $(form).find('table tr.current td input.end')
                .val(currentTime);
            $(button).html('play')
                .removeClass('stop')
                .addClass('play');
            var duration = ( startTime - currentTime ) / 60;
            $(form+' table tr.current td input.duration').val(duration);
        }
    }
    $('form .projectheader .timertoggle').click(function(){
        timertoggle(this);
    });

    function updateProject(projectSelector){
        var project = $(projectSelector).val();
        $.ajax({url: "src/ticketsListAjax.php?project="+project, dataType: "json",
            success: function(result){
                alert(result);
            },
            error: function(result){
            }
        });
    }
    $('form .projectheader select.projectselector').change(function(){
        updateProject(this);
    });



});