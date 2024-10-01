<template>
  <div>Authenticating...</div>
</template>

<script>

export default {
  name: "GoogleAuth",
  created() {
    // Retrieve the token from the URL
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');
    if (token) {
      // Store the token in localStorage
      localStorage.setItem('auth_token', token);

      // You can now redirect to the calendar page
      this.$router.push('/app-calendar');
    } else {
      // Handle error if token is not found
      console.error('No token found in the URL');
      this.$router.push('/login');
    }
  }
}
</script>
