<template>
  <div>
    <button @click="loginWithGoogle">Login with Google</button>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  methods: {
    loginWithGoogle() {
      axios.get('http://localhost:8000/api/google/redirect').then(response => {
        window.location.href = response.data.url;
      });
    }
  }
}
</script>
