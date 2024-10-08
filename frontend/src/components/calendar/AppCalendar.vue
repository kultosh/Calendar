<template>
  <div class="container">
    <calendar-loader v-if="loading" />
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
    <event-form :isVisible="isModalVisible" :formData="selectedEventData" :isEdit="isEventEdit" @close="closeModal" @submit="handleSubmit" @delete="handleDelete" />
  </div>
</template>

<script>
  import FullCalendar from '@fullcalendar/vue';
  import dayGridPlugin from '@fullcalendar/daygrid'
  import timeGridPlugin from '@fullcalendar/timegrid'
  import interactionPlugin from '@fullcalendar/interaction'
  import listMonthPlugin from '@fullcalendar/list'
  import AddUpdateEvent from './AddUpdateEvent.vue'
  import CalendarLoader from './CalendarLoader.vue'
  import axios from 'axios';

  export default {
      name: "AppCalendar",
      components: {
          FullCalendar,
          'event-form': AddUpdateEvent,
          'calendar-loader': CalendarLoader
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
              loading: false,
              url: process.env.VUE_APP_ROOT_API+'/google/events'
          }
      },
      mounted() {
        this.getEvents();
      },
      methods: {
        handleDateSelect(selectInfo) {
          this.selectedEventData = {
            startDate: selectInfo.startStr,
            endDate: selectInfo.endStr
          };
          this.isModalVisible = true;
        },

        handleEventClick(clickInfo) {
          this.selectedEventData = {
            id: clickInfo.event.id,
            startDate: clickInfo.event.startStr,
            endDate: clickInfo.event.endStr,
            title: clickInfo.event.title,
            category: clickInfo.event.extendedProps.extendedProperties ? clickInfo.event.extendedProps.extendedProperties.category : null,
            description: clickInfo.event.extendedProps.description || null,
          };
          this.isEventEdit = true;
          this.isModalVisible = true;
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
          this.loading = true;
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
            description: formData.description
          };

          if(this.isEventEdit) {
            this.updateEvent(this.url+'/'+formData.id, eventData, config, formData);
          } else {
            this.storeEvent(this.url, eventData, config, formData);
          }
        },

        updateEvent(url,data,config,formData) {
          axios.put(url, data, config)
            .then(response => {
              alert(response.data.message);
              const updateIndex = this.calendarOptions.events.findIndex(event => event.id === formData.id);
              if (updateIndex !== -1) {
                this.calendarOptions.events.splice([updateIndex],1);
                this.$nextTick(() => {
                  this.calendarOptions.events.push(response.data.event);
                });
              } else {
                alert('Event not found');
              }
            })
            .catch(error => {
              alert('There was an error adding the event:', error);
            })
            .finally(() => {
              this.loading = false;
            });
        },

        storeEvent(url, data, config, formData) {
          axios.post(url, data, config)
            .then(response => {
              alert(response.data.message);
              if(this.selectedFilterCategorgies.includes(formData.category)) {
                this.calendarOptions.events.push(response.data.event);
              }
            })
            .catch(error => {
              alert('There was an error updating the event:', error);
            })
            .finally(() => {
              this.loading = false;
            });
        },

        handleDelete(id) {
          this.loading = true;
          const token = localStorage.getItem('auth_token');
          const config = {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          };
          axios.delete(this.url+'/'+id, config)
            .then(response => {
              alert(response.data.message);
              const deleteIndex = this.calendarOptions.events.findIndex(event => event.id === id);
              if (deleteIndex !== -1) {
                this.calendarOptions.events.splice([deleteIndex],1);
              } else {
                alert('Event not found with id:', id);
              }
            })
            .catch(error => {
              alert('There was an error deleting the event:', error);
            })
            .finally(() => {
              this.loading = false;
            })
        },

        getEvents(isFilter='false',selectedFilterList=[]) {
          this.loading = true;
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
          axios.get(this.url, config)
            .then(response => {
              this.calendarOptions.events = response.data;
            })
            .catch(error => {
              alert('There was an error fetching the events:', error);
            })
            .finally(() => {
              this.loading = false;
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