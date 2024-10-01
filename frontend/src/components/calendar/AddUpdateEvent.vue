<template>
  <div v-if="isVisible" class="modal">
    <div class="modal-content">
      <span class="close" @click="closeModal">&times;</span>
      <h2>Event Details</h2>
      <form @submit.prevent="submitForm">
        <div class="form-group">
          <label for="title">Title: <span class="event-required" title="REQUIRED FIELD">*</span></label>
          <input type="text" id="title" v-model="form.title" class="form-control" @input="titleError=''" />
          <span class="event-validation-msg" v-if="titleError!==''">{{ titleError }}</span>
        </div>
        <div class="form-group">
          <label for="title">Description: <span class="event-required" title="REQUIRED FIELD">*</span></label>
          <textarea name="description" for="description" id="description" cols="30" rows="4" v-model="form.description" class="form-control"></textarea>
        </div>
        <div class="form-group">
          <label for="startDate">Start Date: <span class="event-required" title="REQUIRED FIELD">*</span></label>
          <input type="date" id="startDate" v-model="form.startDate" class="form-control" :disabled="isFieldDisable" @input="startDateError=''" />
          <span class="event-validation-msg" v-if="startDateError!==''">{{ startDateError }}</span>
        </div>
        <div class="form-group">
          <label for="endDate">End Date: <span class="event-required" title="REQUIRED FIELD">*</span></label>
          <input type="date" id="endDate" v-model="form.endDate" class="form-control" :disabled="isFieldDisable" @input="endDateError=''" />
          <span class="event-validation-msg" v-if="endDateError!==''">{{ endDateError }}</span>
        </div>
        <div class="form-group">
          <label for="category">Category: <span class="event-required" title="REQUIRED FIELD">*</span></label>
          <select id="category" v-model="form.category" class="form-control" @click="categoryError=''">
            <option value="" disabled>Select a category</option>
            <option v-for="(category,index) in categoryList" :key="index" :value="category.value">{{category.text}}</option>
          </select>
          <span class="event-validation-msg" v-if="categoryError!==''">{{ categoryError }}</span>
        </div>
        <button type="submit" class="btn">{{submitButton}}</button>
        <button type="submit" class="btn btn-danger" v-if="isEdit" @click="deleteEvent">{{deleteButton}}</button>
      </form>
    </div>
  </div>
</template>

<script>
  export default {
    props: {
      isVisible: {
        type: Boolean,
        required: true
      },
      formData: {
        type: Object,
        required: false
      },
      isEdit: {
        type: Boolean,
        required: true
      },
    },
    data() {
      return {
        categoryList: [
          {
              value: 'personal',
              text: 'Personal'
          },
          {
              value: 'business',
              text: 'Business'
          },
          {
              value: 'family',
              text: 'Family'
          },
          {
              value: 'holiday',
              text: 'Holiday'
          },
          {
              value: 'etc',
              text: 'ETC'
          }
        ],
        form: {
          id: '',
          title: '',
          startDate: '',
          endDate: '',
          category: '',
          description: ''
        },
        isFieldDisable: false,
        submitButton: 'Submit',
        deleteButton: 'Delete',
        titleError: '',
        startDateError: '',
        endDateError: '',
        categoryError: '',
      };
    },
    methods: {
      closeModal() {
        this.$emit('close');
        this.resetForm();
      },
      submitForm() {
        if(this.isValid()) {
          this.$emit('submit', this.form);
          this.closeModal();
          this.resetForm();
        }
      },
      deleteEvent() {
        this.$emit('delete', this.form.id);
        this.closeModal();
        this.resetForm();
      },
      resetForm() {
        this.form = {
          id: '',
          title: '',
          startDate: '',
          endDate: '',
          category: '',
          descritpion: ''
        };
        this.submitButton = 'Submit';
      },
      isValid() {
        let valid = true;
        if (!this.form.title) {
            this.titleError = "Please enter the 'Title' !";
            valid = false;
        } else {
            this.titleError = '';
        }

        if (!this.form.startDate) {
            this.startDateError = "Please select the 'Start Date' !";
            valid = false;
        } else {
            this.startDateError = '';
        }

        if (!this.form.endDate) {
            this.endDateError = "Please select the 'End Date' !";
            valid = false;
        } else {
            this.endDateError = '';
        }

        if (!this.form.category) {
            this.categoryError = "Please select the 'Category' !";
            valid = false;
        } else {
            this.categoryError = '';
        }

        return valid;
      }
    },
    watch: {
      isVisible(status) {
        if(status) {
          if(!this.isEdit && Object.keys(this.formData).includes('startDate')) {
            this.form.startDate = this.formData.startDate;
            this.form.endDate = this.formData.endDate;
            this.isFieldDisable = true;
          } else if(this.isEdit) {
            this.submitButton = 'Update';
            this.form.id = this.formData.id;
            this.form.startDate = this.formData.startDate;
            this.form.endDate = this.formData.endDate;
            this.form.title = this.formData.title;
            this.form.category = this.formData.category;
            this.form.description = this.formData.description;
          }
        }
      }
    }
  }
</script>

<style scoped>
  .modal {
    display: flex;
    position: fixed;
    z-index: 3;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.5);
    align-items: center;
    justify-content: center;
  }

  .modal-content {
    background-color: white;
    margin: auto;
    padding: 20px;
    border-radius: 5px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  }

  .close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
  }

  .close:hover,
  .close:focus {
    color: black;
    text-decoration: none;
  }

  .form-group {
    margin-bottom: 15px;
  }

  .form-group label {
    display: block;
    margin-bottom: 5px;
  }

  .form-control {
    width: 100%;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
  }

  .btn {
    background-color: #7367F0;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
  }

  .btn:hover {
    background-color: #5a54c3;
  }

  .btn-danger {
    background-color: #dc3545;
    margin-left: 10px;
  }

  .btn-danger:hover {
    background-color: #d11f31;
  }

  .event-required {
    float: right;
    color: #d11f31;
  }

  .event-validation-msg {
    color: #d11f31;
    font-size: 12px;
  }
</style>
