
import { Calendar } from 'fullcalendar';

import dayGridPlugin from 'fullcalendar/daygrid';
import timeGridPlugin from 'fullcalendar/timegrid';
import listPlugin from 'fullcalendar/list';
import interactionPlugin from 'fullcalendar/interaction';

import 'fullcalendar/skeleton.css';
import frLocale from 'fullcalendar/locales/fr';

document.addEventListener('DOMContentLoaded', () => {
    const calendarElement = document.getElementById('calendar');

    if (!calendarElement) {
        return;
    }
    const calendar = new Calendar(calendarElement, {
        plugins: [
            dayGridPlugin,
            timeGridPlugin,
            listPlugin,
            interactionPlugin,
        ],
        
        locales: [frLocale],
        locale: 'fr',
        firstDay: 1,
        initialView: 'dayGridMonth',
        height: 'auto',

        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth',
        },

        buttonText: {
            today: "Aujourd'hui",
            month: 'Mois',
            week: 'Semaine',
            day: 'Jour',
            list: 'Liste',
        },

        events: [],

        selectable: true,
        editable: false,
    });

    calendar.render();
});
