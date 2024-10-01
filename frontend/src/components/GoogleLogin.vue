<template>
  <div class="login-container">
    <button @click="loginWithGoogle" class="google-login-btn">
      <img src="/img/g-logo.png" alt="Google logo" />
      <span>Sign in with Google</span>
    </button>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  methods: {
    loginWithGoogle() {
      axios.get(process.env.VUE_APP_ROOT_API+'/google/redirect')
        .then(response => {
          window.location.href = response.data.url;
        })
        .catch(error => {
          console.error('Google login error:', error);
        });
    }
  }
}
</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  background-color: #f5f5f5;
}

.google-login-btn {
  display: flex;
  align-items: center;
  background-color: white;
  color: #757575;
  border: 1px solid #ddd;
  border-radius: 5px;
  padding: 10px 15px;
  font-size: 16px;
  cursor: pointer;
  transition: background-color 0.3s;
  box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
}

.google-login-btn img {
  width: 20px;
  margin-right: 10px;
}

.google-login-btn:hover {
  background-color: #f7f7f7;
}

.google-login-btn span {
  font-weight: 500;
  color: #4285F4;
}
</style>
