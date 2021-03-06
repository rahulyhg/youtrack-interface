$(document).ready(function(){
    $('input, select, textarea, button').prop('disabled', false);

    /**
     * create new set of fields for a new ticket
     */
    function duplicateTableRow(){
        var html = '<tr>' + $('#toBeImported tr.hidden').last().html() + '</tr>';
        $('#toBeImported table tbody').append( html );
    }
    /**
     * update an elements attribute
     * @param inputField {object} jquery object of field
     * @param attributeName {string} attribute to update
     * @param row {int} ticket row number
     */
    function updateInputAttribute(inputField, attributeName, row){
        var attribute = inputField.attr( attributeName );
        if (typeof attribute !== 'undefined') {
            var name = attribute.substring( 0, attribute.lastIndexOf("-") );
            attribute = name + '-' + row;
            inputField.attr(attributeName,attribute);
        }
    }
    /**
     * update the input elements name & group attributes in the new ticket row
     * @param row {int} ticket row number
     */
    function tableInputAttributeUpdate(row){
        $('#toBeImported table tbody tr:last td input').each(function(){
            updateInputAttribute($(this),'name',row);
        });
        $('#toBeImported table tbody tr:last td select').each(function(){
            updateInputAttribute($(this),'name',row);
        });
        $('#toBeImported table tbody tr:last td textarea').each(function(){
            updateInputAttribute($(this),'name',row);
        });
        $('#toBeImported table tbody tr:last td input[group]').each(function(){
            updateInputAttribute($(this),'group',row);
        });
        $('#toBeImported table tbody tr:last').attr('row',row);
    }
    /**
     * add row to table button
     */
    $('#addRowToTable').on('click',function(){
        var row = $('#toBeImported table tbody tr:last').attr('row');
        row++;
        duplicateTableRow();
        // update names and group for that row
        tableInputAttributeUpdate(row);
        // update the ui
        updateUi("tr:last");
    });


    $('#HideFields').accordion({collapsible: true});
    /**
     * hide or show the a column for all ticket fields
     * @param checkbox {object} jquery object
     */
    function hideOrShowField(checkbox){
        var name = checkbox.attr('name');
        if( checkbox.is(':checked') ){
            $('#toBeImported table tr th.'+name.replace(/\ /g,'')+'column').hide();
            $('#toBeImported table tr td.'+name.replace(/\ /g,'')+'column').hide();
        }else{
            $('#toBeImported table tr th.'+name.replace(/\ /g,'')+'column').show();
            $('#toBeImported table tr td.'+name.replace(/\ /g,'')+'column').show();
        }
    };
    $('#HideFields input').each(function(){
        hideOrShowField( $(this) );
    });
    $('#HideFields input').change(function(){
        hideOrShowField( $(this) );
    });
    $('#HideFields').bind('DOMNodeInserted DOMNodeRemoved', function() { 
        $('#HideFields input').change(function(){
            hideOrShowField( $(this) );
        });
    });

    /**
     * add needed or remove not needed hide/show checkboxes
     */
    function updateHideFieldsCheckboxes(){
        $('form#toBeImported th').each(function(){
            var name = $(this).text();
            switch(name) {
                case 'project':
                case 'Assignee':
                case 'summary':
                case 'description': 
                    return;
                    break;
            }
            // if checkbox dosnt exist
            if ( !$( '#HideFields input[name="'+name+'"]' ).length && name.length !== 0) {
                $('#HideFields #HideFieldsCheckBoxContainer').append('<label><input type="checkbox" name="'+name+'" value="'+name+'">'+name+'</label>');
            }
        });
    }

    /**
     * does element exist?
     * @param selector {string} css selector
     * @param children {boolean} check element for children
     * @returns {boolean}
     */
    function elementExists(selector,children){
        if( children === true){
            selected = $(selector).children();
        }else{
            selected = $(selector);    
        }
        if(selected.length){
            return true;
        }else{
            return false;
        }
    }

    /**
     * create a field that dosent yet exist for the new ticket row, but is needed
     * @param selector {string} css selector
     * @param fieldName {string} name on the field
     * @param row {int} row reference of the new field
     * @param result {array} data of field from code/createByFormAjax.php
     */
    function createField(selector,fieldName,row, result){
        var html;
        var name = fieldName.replace(/\ /g,'¬')+'-'+row;
        var fieldType = result[fieldName]['fieldType'];
        var dropdown = (fieldType.indexOf("[") > -1);
        switch(fieldType){
            case 'string':
                html = '<input type="text" name="' + name + '"/>';
                break;
            case 'period':
                 html = '<fieldset class="spinnerFieldset"><input class="spinner weekSpinner" value="0" group="'+name+'" ><label class="spinnerLabel">w</label><input class="spinner daySpinner" value="0"  group='+name+'" ><label class="spinnerLabel">d</label><input class="spinner hourSpinner" value="0" group="'+name+'" ><label class="spinnerLabel">h</label><input class="spinner minSpinner" value="0"  group="'+name+'" ><label class="spinnerLabel">m</label><input class="hidden spinnerInput" name="'+name+'" ></fieldset>';
                break;
            case 'date':
                html = '<input type="text" class="datepicker" size="30" name="'+name+'" />';
        }
        $(selector).html(html);
        
        if(dropdown){
            html = '<select name="' + name + '"></select>';
            $(selector).html(html);
        }
        
    }

    /**
     * update new ticket dropdown fields with the available options
     * @param selector {string} css selector
     * @param fieldName {string} name on the field
     * @param result {array} data of field from code/createByFormAjax.php
     */
    function updateField(selector,fieldName, result){
        var fieldType = result[fieldName]['fieldType'];
        var dropdown = (fieldType.indexOf("[") > -1);
        if(dropdown){
            $(selector).html(result[fieldName]['innerHtml']);
        }
    }
    
    /**
     * disables and puts a line through fields not valid for current project's tickets
     * 
     * @param {array} customFieldData field data from youtrack 
     * @param {int} row row in the form
     */
    function removeUnwantedFields(customFieldData,row){
        var classList = [];
        $("#toBeImported table tr[row='" + row + "'] td").each(function(){
            if($(this).attr('class')){
                classList[$(this).attr('class')]=$(this).attr('class');
            }
        });
        // remove valid fields from remove list
        for ( var fieldName in customFieldData ){
            var fieldNameNoSpaces = fieldName.replace(/\ /g,'');
            if(classList[fieldNameNoSpaces+'column']){
                delete classList[fieldNameNoSpaces+'column'];
            }
        }
        delete classList['ticketLinkscolumn linkscolumn'];
        delete classList['attachmentscolumn'];
        for ( var singleClass in classList){
            singleClass = singleClass.replace(' ','.');
            $("#toBeImported table tr[row='" + row + "'] td."+singleClass).html('<hr/>');
        }
    }

    /**
     * update the project row for the new project given
     * @param projectSelector {string} project selector
     */
    function updateProjectRowFromProjectSelector(projectSelector){
        var name = $(projectSelector).attr('name');
        $('#loadingScreen').show();
        var project = $(projectSelector).val();
        var explodedName = name.split('-');
        var row = explodedName[1];
        $("#toBeImported table tr[row='" + row + "'] select:not(.dontClear)").html('<option value=""></option>');
        $.ajax({url: "code/createByFormAjax.php?project="+project, dataType: "json",
            success: function(result){
                removeUnwantedFields(result,row);
                for ( var fieldName in result ){
                    if ( typeof(fieldName) !== "undefined" && result.hasOwnProperty(fieldName) && fieldName!=='assignee') {
                        var fieldNameNoSpaces = fieldName.replace(/\ /g,'');

                        // if column dosent exist add to header
                        if( !elementExists('#toBeImported table tr th.' + fieldNameNoSpaces + 'column',false) ){
                            $('#toBeImported table tr').first().append('<th class="'+fieldNameNoSpaces+'column">'+fieldName+'</th>');
                            $('#toBeImported table tr[row]').append('<td class="'+fieldNameNoSpaces+'column"></td>');
                        }

                        // if field dosent exist create it or just update it
                        if(!elementExists('#toBeImported table tr[row="' + row + '"] td.'+fieldNameNoSpaces+'column',true)){
                            createField('#toBeImported table tr[row="' + row + '"] td.'+fieldNameNoSpaces+'column', fieldName, row, result);
                            updateField('#toBeImported table tr[row="' + row + '"] td.'+fieldNameNoSpaces+'column select', fieldName, result);
                        }else{
                            updateField('#toBeImported table tr[row="' + row + '"] td.'+fieldNameNoSpaces+'column select', fieldName, result);
                        }
                    }
                }
                updateField('#toBeImported table tr[row="' + row + '"] td select[name="assignee-'+row+'"', 'assignee', result);
                updateHideFieldsCheckboxes();

               // updateUi('tr[row="' + row + '"]'); // not needed i dont think. needs testing
                $('#loadingScreen').hide();
            },
            error: function(result){
                alert( result['status'] + ': ' + result['statusText'] );
                $('#loadingScreen').hide();
            }
        });
    }
    /**
     * update the hidden row used as a template for new ticket rows
     * @param projectSelector {object} jquery object of the project select element
     */
    function updateHiddenRow(projectSelector){
        var name = $(projectSelector).attr('name');
        var explodedName = name.split('-');
        var row = explodedName[1];
        $('#toBeImported table tr.hidden select.projectselector').val( $(projectSelector).val() );
        $("#toBeImported table tr[row='" + row + "'] select:not(.dontClear)").each(function(){
            var name = $(this).attr('name');
            var explodedName = name.split('-');
            var variableName = explodedName[0];
            $('#toBeImported table tr.hidden select[name='+variableName+'-0]').html( $(this).html() );
        });
    }
    /**
     * update the hidden input for duration field, when the visible spinner inputs for that field is edited.
     * @param spinner {string} css selector of spinner input (not the hidden input)
     */
    function updateSpinnerValue(spinner){
        var val ='';
        var group = $(spinner).attr('group');
        var week = $('table tr:not(.hidden) input.weekSpinner[group="'+group+'"]').val();
        var day = $('table tr:not(.hidden) input.daySpinner[group="'+group+'"]').val();
        var hour = $('table tr:not(.hidden) input.hourSpinner[group="'+group+'"]').val();
        var min = $('table tr:not(.hidden) input.minSpinner[group="'+group+'"]').val();
        if( week > 0){
            val += week+'w';
        }
        if( day > 0){
            val += day+'d';
        }
        if( hour > 0){
            val += hour+'h';
        }
        if( min > 0){
            val += min+'m';
        }
        $('input[name="'+group+'"]').val(val);
    }
    /**
     * enable the user interfaces for the new ticket row
     * @param selector {string} css selector of the new row. "#toBeImported table "+selector must be valid css selector.
     */
    function updateUi(selector){
        var selector = selector || '';
        /**
         * set fields as spinners
         */
        $("#toBeImported table "+selector+" .weekSpinner" ).spinner({
          spin: function( event, ui ) {
            if ( ui.value < 0 ) {
              $( this ).spinner( "value", 0 );
              return false;
            }
          }
        });
        $("#toBeImported table "+selector+" .daySpinner" ).spinner({
          spin: function( event, ui ) {
            if ( ui.value > 6 ) {
                $( this ).spinner( "value", 0 );
                return false;
            } else if ( ui.value < 0 ) {
                $( this ).spinner( "value", 6 );
                return false;
            }
          }
        });
        $("#toBeImported table "+selector+" .hourSpinner" ).spinner({
          spin: function( event, ui ) {
            if ( ui.value > 23 ) {
              $( this ).spinner( "value", 0 );
              return false;
            } else if ( ui.value < 0 ) {
              $( this ).spinner( "value", 23 );
              return false;
            }
          }
        });
        $("#toBeImported table "+selector+" .minSpinner" ).spinner({
          spin: function( event, ui ) {
            if ( ui.value > 59 ) {
              $( this ).spinner( "value", 0 );
              return false;
            } else if ( ui.value < 0 ) {
              $( this ).spinner( "value", 59 );
              return false;
            }
          }
        });
        /**
         * spinner change update hidden field on spinner change
         */
        $("#toBeImported table "+selector+' .spinner').spinner({
            stop:function(e,ui){
                updateSpinnerValue(this);
            }
        });
        // $("#toBeImported table "+selector+' .spinnerInput').change(function(){
        //     var val = $(this).val();
        //     var group = $(this).attr('name');
        //     // check valid format 1w1d1h1m or 1w1m
        //     var closestValid = val.match(/((\d)+w)?(\dd)?((\d|\d\d)h)?((\d|\d\d)m)?/);
        //     if( closestValid[0] === val ){
        //         var str = val;
        //         if( str.search('w')>-1 ){
        //             var myarr = str.split('w');
        //             var w = myarr[0];
        //             str = myarr[1];
        //         }else{
        //             var w = '';
        //         }
        //         if( str.search('d')>-1 ){
        //             var myarr = str.split('d');
        //             var d = myarr[0];
        //             str = myarr[1];
        //         }else{
        //             var d = '';
        //         }
        //         if( str.search('h')>-1 ){
        //             var myarr = str.split('h');
        //             var h = myarr[0];
        //             str = myarr[1];
        //         }else{
        //             var h = '';
        //         }
        //         if( str.search('m')>-1 ){
        //             var myarr = str.split('m');
        //             var m = myarr[0];
        //             str = myarr[1];
        //         }else{
        //             var m = '';
        //         }
        //         if( (typeof d === 'undefined' || d<7 )  && (typeof h === 'undefined' || h < 24 ) && (typeof m === 'undefined' || m < 60 ) ){
        //             w = parseInt(w);
        //             $("#toBeImported table tr:last .weekSpinner[group='"+group+"']").spinner("value", w);
        //             $("#toBeImported table .daySpinner[group='"+group+"']").spinner("value",d);
        //             $("#toBeImported table .hourSpinner[group='"+group+"']").spinner("value",h);
        //             $("#toBeImported table .minSpinner[group='"+group+"']").spinner("value",m);
        //             isValid = true;
        //         }else{
        //             isValid = false;
        //         }
        //     }else{
        //         isValid = false;
        //     }
        //     if( isValid === false ){
        //         alert('invalid duration');
        //     }
        // });
        /**
         * date picker
         */
        $("#toBeImported table "+selector+" .datepicker" ).datepicker({dateFormat: 'yy-mm-dd' });
        /**
         * project selector
         */
        $('#toBeImported table '+selector+' select.projectselector').change(function(){
           updateProjectRowFromProjectSelector(this);
            updateHiddenRow(this);
        });
    }
    updateUi("tr:last");

    /**
     * submit pre processing
     */
    $('#toBeImported').submit(function() {
        preSubmit();
    });
    function preSubmit() {
        // format datepicker data
        $('#toBeImported table tr:not(.hidden) input.datepicker').each(function () {
            var val = $(this).val();
            var date = new Date(val);
            var time = date.getTime(date);
            if (time) {
                $(this).val(time);
            } else {
                $(this).val('');
            }
        });
        // format ticket links
        $('#toBeImported table tr:not(.hidden) .ticketLinkscolumn').each(function () {
            var linkCommand = '';
            $(this).find('.ticketLinks .singleLink').each(function () {
                var linkType = $(this).find('.linkType').first().val();
                var linkProjectSelector = $(this).find('.linkProjectSelector').first().val();
                var linkTicketNumber = $(this).find('.linkTicketNumber').first().val();
                if (linkType && linkProjectSelector && linkTicketNumber) {
                    linkCommand = linkCommand + ' '
                            + linkType + ' '
                            + linkProjectSelector + '-'
                            + linkTicketNumber;
                }
            });
            $(this).find('.linkInputField').val(linkCommand);
        });
    }


    $('#toBeImported').on('click', '#ajaxSubmit', function(){
        $(this).prop('disabled', true);
        preSubmit();
        var form = $('#toBeImported');
        var formElement = document.querySelector('#toBeImported');
        $.ajax({
            dataType: "json",
            type: form.attr('method'),
            url: form.attr('action') + '?ajax=true',
            data: new FormData( form[0] ),
            processData: false,
            contentType: false,
            success: function (result) {
                for ( var ticket in result ){
                    var html = '<div><a href="'+result[ticket]['url']+'" target="_blank">' + result[ticket]['ticketRef'] + '</a></div>';
                    if(result[ticket]['uploaded']){
                        html += '<div>uploaded</div>';
                    }else{
                        html += '<div>upload failed</div>';
                    }
                    if(result[ticket]['updated']){
                        html += '<div>updated</div>';
                    }else{
                        html += '<div>update failed</div>';
                    }
                    var row = $( '#toBeImported tr[row=' + result[ticket]['row'] + ']');
                    row .children('td.success').html(html);
                    row.find('input, textarea').prop('readonly', true)
                    row.find('button, select').prop('disabled',true);
                    row.find('input, select, textarea, button')
                            .addClass('submitSuccess')
                            .removeAttr('name');
                };
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert('submit fail');
                console.log(textStatus, errorThrown);
            }
        });
        $(this).prop('disabled', false);
    });

});