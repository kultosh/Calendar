<template>
  <div class="container">
    <div class="calendar-row">
      <div class="calendar-col">
        <div class="sidebar">
          <button class="calendar-btn" @click="openModal">Add New</button>
          <div class="calendar-filter">
            <label for="filter">FILTER</label>
            <ul>
              <li v-for="(filterCategory,index) in filterList" :key="index">
                <input type="checkbox" :id="filterCategory.value" v-model="filterCategory.isCheck" @click="handleFilterEvent($event,filterCategory.value,index)">
                <label :for="filterCategory.value">{{filterCategory.text}}</label>
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
import axios from 'axios';

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
                  events: []
              },
              isModalVisible: false,
              isEventEdit: false,
              selectedEventData: {},
              selectedFilterCategorgies: ['view-all','personal','business','family','holiday','etc'],
              filterList: [
                {
                  text: 'View All',
                  value: 'view-all',
                  isCheck: true,
                },
                {
                  text: 'Personal',
                  value: 'personal',
                  isCheck: true,
                },
                {
                  text: 'Business',
                  value: 'business',
                  isCheck: true,
                },
                {
                  text: 'Family',
                  value: 'family',
                  isCheck: true,
                },
                {
                  text: 'Holiday',
                  value: 'holiday',
                  isCheck: true,
                },
                {
                  text: 'ETC',
                  value: 'etc',
                  isCheck: true,
                }
              ],
          }
      },
      mounted() {
        this.getEvents();
      },
      methods: {
        handleWeekendsToggle() {
          this.calendarOptions.weekends = !this.calendarOptions.weekends
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
          const currentEvent =  {
                                color: eventColor.background,
                                textColor: eventColor.textColor,
                                start: formData.startDate,
                                end: formData.endDate,
                                title: formData.title,
                                category: formData.category
                              };
          const token = localStorage.getItem('auth_token');
          const config = {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          };

          const eventData = {
            summary: formData.title,
            start: formData.startDate,
            end: formData.endDate,
            category: formData.category,
          };

          axios.post('http://localhost:8000/api/google/events', eventData, config)
            .then(response => {
              alert(response.data.message);
              if(this.selectedFilterCategorgies.includes(formData.category)) {
                this.calendarOptions.events.push(currentEvent);
              }
            })
            .catch(error => {
              console.error('There was an error adding the event:', error);
            });
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
        },

        getEvents(isFilter='false',selectedFilterList=[]) {
          const token = localStorage.getItem('auth_token');
          const config = {
            headers: {
              Authorization: `Bearer ${token}`,
            },
            params: {
              'isFilter': isFilter,
              'selectedFilterList': selectedFilterList
            }
          };

          axios.get('http://localhost:8000/api/google/events', config)
            .then(response => {
              console.log('fetchEventList>>', response.data);
              this.calendarOptions.events = response.data;

            })
            .catch(error => {
              console.error('There was an error adding the event:', error);
            });
        },

        handleFilterEvent(event,filterCategory,index) {
          if(filterCategory==='view-all') {
            if(event.target.checked) {
              this.filterList.map(data => {
                data.isCheck = true;
                this.selectedFilterCategorgies.push(data.value);
              });
              this.getEvents();
            } else {
              this.filterList.map(data => {
                data.isCheck = false;
              });
              this.selectedFilterCategorgies=[];
              this.calendarOptions.events = [];
            }
            return true;
          }
          
          if(event.target.checked) {
            this.filterList[index].isCheck = true;
            this.selectedFilterCategorgies.push(filterCategory);
          } else {
            this.filterList[0].isCheck = false;
            this.filterList[index].isCheck = false;
            const selectedFilterList = this.selectedFilterCategorgies;
            this.selectedFilterCategorgies = selectedFilterList.filter(data => {
              return data !== filterCategory;
            });
          }

          if(this.selectedFilterCategorgies.length==0) {
            this.calendarOptions.events = [];
          } else {
            this.getEvents('true',this.selectedFilterCategorgies);
          }
        },
      }
  }
</script>