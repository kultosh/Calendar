<template>
  <div class="container">
    <div class="calendar-row">
      <div class="calendar-col">
        <div class="sidebar">
          <button class="calendar-btn" @click="openModal">Add New</button>
          <div class="calendar-filter">
            <label for="filter">FILTER</label>
            <ul>
              <li>
                <input type="checkbox" id="view-all">
                <label for="view-all">View All</label>
              </li>
              <li>
                <input type="checkbox" id="personal">
                <label for="personal">Personal</label>
              </li>
              <li>
                <input type="checkbox" id="business">
                <label for="business">Business</label>
              </li>
              <li>
                <input type="checkbox" id="family">
                <label for="family">Family</label>
              </li>
              <li>
                <input type="checkbox" id="holiday">
                <label for="holiday">Holiday</label>
              </li>
              <li>
                <input type="checkbox" id="etc">
                <label for="etc">ETC</label>
              </li>
            </ul>
          </div>
          <div class="sidebar-img">
            <img src="/img/Image.png" alt="Calendar Sidebar">
          </div>
        </div>
      </div>
      <div class="calendar-col">
        <FullCalendar
          class='demo-app-calendar'
          :options='calendarOptions'
        >
          <template v-slot:eventContent='arg'>
            <b>{{ arg.timeText }}</b>
            <i>{{ arg.event.title }}</i>
          </template>
        </FullCalendar>
      </div>
    </div>
    <event-form :isVisible="isModalVisible" :formData="selectedEventData" :isEdit="isEventEdit" @close="closeModal" @submit="handleSubmit" />
  </div>
</template>

<script>
  import FullCalendar from '@fullcalendar/vue';
  import dayGridPlugin from '@fullcalendar/daygrid'
  import timeGridPlugin from '@fullcalendar/timegrid'
  import interactionPlugin from '@fullcalendar/interaction'
  import listMonthPlugin from '@fullcalendar/list'
  import AddNewEvent from './components/AddNewEvent.vue'

  export default {
      name: "AppCalendar",
      components: {
          FullCalendar,
          'event-form': AddNewEvent,
      },
      data: function() {
          return {
              calendarOptions: {
                  plugins: [
                    dayGridPlugin,
                    timeGridPlugin,
                    interactionPlugin,
                    listMonthPlugin
                  ],
                  headerToolbar: {
                      left: 'prev,next title',
                      right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
                  },
                  buttonText: {
                    today: 'Today',
                    month: 'Month',
                    week: 'Week',
                    day: 'Day',
                    list: 'List'
                  },
                  initialView: 'dayGridMonth',
                  editable: true,
                  selectable: true,
                  selectMirror: true,
                  dayMaxEvents: 2,
                  height: 'auto',
                  weekends: true,
                  select: this.handleDateSelect,
                  eventClick: this.handleEventClick,
                  eventsSet: this.handleEvents,
                  events: [{id: 1, color: '#00CFE81F', textColor: '#00CFE8', start: '2024-09-24', end: '2024-09-27', title: 'First Event'}]
              },
              isModalVisible: false,
              isEventEdit: false,
              selectedEventData: {},
          }
      },
      mounted() {
        /** Test */
        const getEvents = JSON.parse(localStorage.getItem('calendarEventList'));
        if(!!getEvents && getEvents.length > 0) {
          this.calendarOptions.events = getEvents;
        }
      },
      methods: {
        handleWeekendsToggle() {
          this.calendarOptions.weekends = !this.calendarOptions.weekends // update a property
        },

        handleDateSelect(selectInfo) {
          this.selectedEventData = {
            startDate: selectInfo.startStr,
            endDate: selectInfo.endStr
          };
          this.isModalVisible = true;
        },

        handleEventClick(clickInfo) {
          if (confirm(`Are you sure you want to delete the event '${clickInfo.event.title}'`)) {
            let filterEventList = this.calendarOptions.events.filter((dayEvent) => {
              return dayEvent.id !== parseInt(clickInfo.event.id);
            });
            this.calendarOptions.events = filterEventList;
            localStorage.setItem('calendarEventList', JSON.stringify(filterEventList));
          }
        },

        handleEvents(events) {
          this.currentEvents = events
        },

        openModal() {
          this.isModalVisible = true;
        },

        closeModal() {
          this.isModalVisible = false;
          this.isEventEdit = false;
          this.selectedEventData = {};
        },

        handleSubmit(formData) {
          const eventColor = this.getEventColor(formData.category);
          const curretnEvent =  {
                                id: this.calendarOptions.events.length > 0 ? this.calendarOptions.events.length+1 : 1,
                                color: eventColor.background,
                                textColor: eventColor.textColor,
                                start: formData.startDate,
                                end: formData.endDate,
                                title: formData.title
                              };
          this.calendarOptions.events.push(curretnEvent);
          localStorage.setItem('calendarEventList', JSON.stringify(this.calendarOptions.events));
        },

        getEventColor(category) {
          let selectedColor = {};
          switch(category) {
            case 'personal':
              selectedColor = { background: '#28C76F1F', textColor: '#28C76F'};
              break;
            case 'family':
              selectedColor = { background: 'rgb(255 171 0 / 11%)', textColor: '#FF9F43'};
              break;
            case 'holiday':
              selectedColor = { background: '#28C76F1F', textColor: '#28C76F'};
              break;
            case 'etc':
              selectedColor = { background: '#00CFE81F', textColor: '#00CFE8'};
              break;
            default:
              selectedColor = { background: 'rgb(105 108 255 / 21%)', textColor: '#7367F0'};
          }
          return selectedColor;
        }
      }
  }
</script>