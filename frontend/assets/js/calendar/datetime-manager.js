/**
 * Manages datetime picker functionality
 * Location: frontend/js/calendar/datetime-manager.js
 */
import { Config } from '../core/config.js';
import { Utils } from '../core/utils.js';

export const DateTimeManager = (() => {
    const initializeDateTimePickers = () => {
        const options = {
            format: 'Y-m-d H:i',
            formatTime: 'H:i',
            formatDate: 'Y-m-d',
            step: Config.calendar.timeInterval,
            minDate: false,
            maxDate: false,
            defaultTime: '09:00',
            timepicker: true,
            datepicker: true,
            weeks: false,
            theme: 'default',
            lang: 'en',
            yearStart: new Date().getFullYear() - 5,
            yearEnd: new Date().getFullYear() + 5,
            todayButton: true,
            closeOnDateSelect: false,
            closeOnTimeSelect: true,
            closeOnWithoutClick: true,
            timepickerScrollbar: true,
            onSelectDate: function(ct, $i) {
                if ($i.attr('id') === 'eventStart') {
                    const endPicker = $('#eventEnd');
                    if (!endPicker.val()) {
                        const endTime = new Date(ct.getTime() + 60 * 60 * 1000);
                        endPicker.datetimepicker('setOptions', {
                            value: endTime,
                            minDate: ct
                        });
                    } else {
                        endPicker.datetimepicker('setOptions', { minDate: ct });
                    }
                }
            },
            onSelectTime: function(ct, $i) {
                if ($i.attr('id') === 'eventStart') {
                    const endPicker = $('#eventEnd');
                    if (!endPicker.val()) {
                        const endTime = new Date(ct.getTime() + 60 * 60 * 1000);
                        endPicker.datetimepicker('setOptions', {
                            value: endTime,
                            minDate: ct
                        });
                    } else {
                        endPicker.datetimepicker('setOptions', { minDate: ct });
                    }
                }
            }
        };
        
        $('#eventStart, #eventEnd').datetimepicker(options);
        
        $('#eventStart').on('change', function() {
            const startDate = $(this).datetimepicker('getValue');
            if (startDate) {
                $('#eventEnd').datetimepicker('setOptions', { minDate: startDate });
                
                const endDate = $('#eventEnd').datetimepicker('getValue');
                if (!endDate || endDate <= startDate) {
                    const newEndDate = new Date(startDate.getTime() + 60 * 60 * 1000);
                    $('#eventEnd').datetimepicker('setOptions', { value: newEndDate });
                }
            }
        });
    };
    
    const setDateTimeValues = (startDate, endDate) => {
        if (startDate) {
            const start = Utils.parseEventDateTime(startDate);
            $('#eventStart').datetimepicker('setOptions', { value: start });
        }
        
        if (endDate) {
            const end = Utils.parseEventDateTime(endDate);
            $('#eventEnd').datetimepicker('setOptions', { value: end });
        }
    };
    
    const clearDateTimeValues = () => {
        $('#eventStart, #eventEnd').val('');
    };
    
    const getDateTimeValues = () => {
        return {
            start: $('#eventStart').val(),
            end: $('#eventEnd').val()
        };
    };
    
    const setDefaultDateTime = () => {
        const now = new Date();
        const roundedMinutes = Math.ceil(now.getMinutes() / Config.calendar.timeInterval) * Config.calendar.timeInterval;
        now.setMinutes(roundedMinutes, 0, 0);
        
        const endTime = new Date(now.getTime() + 60 * 60 * 1000);
        
        $('#eventStart').datetimepicker('setOptions', { value: now });
        $('#eventEnd').datetimepicker('setOptions', { value: endTime });
    };
    
    return {
        initializeDateTimePickers,
        setDateTimeValues,
        clearDateTimeValues,
        getDateTimeValues,
        setDefaultDateTime
    };
})();