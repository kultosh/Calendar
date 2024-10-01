<p align="center"><a href="https://share.nmblc.cloud/355807c4" target="_blank"><img src="https://share.nmblc.cloud/355807c4" width="400" alt="Calendar"></a></p>

## About Calendar

This project integrates Google Calendar with a frontend built using Vue.js and a backend built using Laravel. The application allows users to authenticate via Google OAuth and perform CRUD operations on their Google Calendar events along with filtering the events.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Usage](#usage)
5. [API Endpoints](#api-endpoints)
6. [Technologies Used](#technologies-used)
7. [Contributing](#contributing)

## Prerequisites

Before you begin, ensure you have the following installed on your local machine:

- [Node.js](https://nodejs.org/) (v14.x or higher)
- [Vue CLI](https://cli.vuejs.org/)
- [PHP](https://www.php.net/) (v8.0 or higher)
- [Composer](https://getcomposer.org/)
- [Google API Client Library](https://github.com/googleapis/google-api-php-client)
  
You will also need a Google account and to set up Google API credentials via [Google Cloud Console](https://console.cloud.google.com/).

## Installation

### Clone the repository:

```bash
git clone https://github.com/kultosh/Calendar.git
cd calendar
```

### Install the dependencies :

#### For the Laravel Backend:

```bash
composer install
```
#### For the Vue Frontend:
```bash
cd frontend
npm install
```

## Configuration

### Google API Setup
1. Go to the [Google Cloud Console](https://console.cloud.google.com/).
2. Create a new project and enable the "Google Calendar API."
3. Create OAuth 2.0 credentials and get your GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET
4. Also please add Authorised redirect URLs as "your-backend-url/api/auth/google/callback"
5. Add the following credentials to your .env file in the Laravel root directory:
```bash
GOOGLE_CLIENT_ID="your-client-id"
GOOGLE_CLIENT_SECRET="your-client-secret"
GOOGLE_REDIRECT_URI="your-backend-url/api/auth/google/callback"
GOOGLE_SCOPE="https://www.googleapis.com/auth/calendar"
GOOGLE_TIMEZONE="Asia/Kathmandu"
```

## Usage

### Running the Application

#### For the backend:
```bash
php artisan serve
```

#### For the frontend:
```bash
cd frontend
npm run serve
```

## Google OAuth Authentication

1. Visit the login page at your-frontend-url/login.
2. Click the "Login with Google" button to authenticate.
3. After authentication, you will be redirected to the calendar page, where you can view and manage events.


## API Endpoints

| HTTP Method | Endpoint                         | Description                                 |
|-------------|----------------------------------|---------------------------------------------|
| `GET`       | `/api/auth/google/redirect`      | Redirects to Google OAuth for authentication|
| `GET`       | `/api/auth/google/callback`      | Handles the Google OAuth callback           |
| `GET`       | `/api/google/events`             | Fetches all events from Google Calendar     |
| `POST`      | `/api/google/events`             | Adds a new event to Google Calendar         |
| `PUT`       | `/api/google/events/{eventId}`   | Updates an existing event in Google Calendar|
| `DELETE`    | `/api/google/events/{eventId}`   | Deletes an event from Google Calendar       |


## Middleware Protected Routes
- The /google/events endpoints are protected by Laravel Sanctum, so ensure that the user is authenticated before making any API requests.


## Technologies Used
- Frontend: Vue.js, Axios, FullCalendar, VueRouter
- Backend: Laravel, Sanctum, Google API Client
- API: Google Calendar API


## Contributing
Feel free to fork this repository and make your changes. If you would like to contribute, submit a pull request.
