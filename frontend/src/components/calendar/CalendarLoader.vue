<template>
    <div class="loader-overlay">
      <div class="loader"></div>
    </div>
</template>

<style scoped>
.loader-overlay {
    display: flex; /* Use flex to center the loader */
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7); /* Semi-transparent background */
    justify-content: center;
    align-items: center;
    z-index: 9999; /* Ensure it is on top of everything */
}

.loader {
    border: 8px solid #f3f3f3; /* Light grey */
    border-top: 8px solid #3498db; /* Blue */
    border-radius: 50%;
    width: 50px; /* Size of the spinner */
    height: 50px;
    animation: spin 1s linear infinite; /* Spin animation */
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>