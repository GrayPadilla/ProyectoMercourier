jQuery(document).ready(function($){
    // Check if Frontend Manager Date Picker is disable
    if( !wpcfeDateTimeAjaxhandler.disableDatepicker ){
        $('.wpccf-datepicker').pickadate({
            format: wpcfeDateTimeAjaxhandler.dateFormat,
        });
    }
    // Check if Frontend Manager Time Picker is disable
    if( !wpcfeDateTimeAjaxhandler.disableTimepicker ){
        $('.wpccf-timepicker').pickatime({
            twelvehour: wpcfeDateTimeAjaxhandler.timeFormat,
        });
    }
    // Get the elements
    var from_input = $('.daterange_picker.start_date').pickadate({
        format: 'yyyy-mm-dd',
    }),
    from_picker = from_input.pickadate('picker');
    var to_input = $('.daterange_picker.end_date').pickadate({
        format: 'yyyy-mm-dd',
    }),
    to_picker = to_input.pickadate('picker');
    
    if( from_picker && to_picker ){
        // Check if there’s a “from” or “to” date to start with and if so, set their appropriate properties.
        if (from_picker.get('value')) {
            to_picker.set('min', from_picker.get('select'))
        }
        if (to_picker.get('value')) {
            from_picker.set('max', to_picker.get('select'))
        }
        
        // Apply event listeners in case of setting new “from” / “to” limits to have them update on the other end. If ‘clear’ button is pressed, reset the value.
        from_picker.on('set', function (event) {
            if (event.select) {
                to_picker.set('min', from_picker.get('select'))
            } else if ('clear' in event) {
                to_picker.set('min', false)
            }
        });
        to_picker.on('set', function (event) {
            if (event.select) {
                from_picker.set('max', to_picker.get('select'))
            } else if ('clear' in event) {
                from_picker.set('max', false)
            }
        });
    }
});