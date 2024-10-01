<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Oauth2 as GoogleOauth2;
use App\Models\User;

class GoogleCalendarService
{
    private $client;
    private $timezone;

    public function __construct()
    {
        $this->client = new GoogleClient();
        $this->client->setClientId(env('GOOGLE_CLIENT_ID'));
        $this->client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $this->client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
        $this->client->addScope([env('GOOGLE_SCOPE'), 'email', 'profile']); // Include profile scope
        $this->client->setAccessType('offline'); // Ensure offline access to get the refresh token
        $this->client->setPrompt('consent'); // Force the prompt to ensure we get the refresh token
        $this->timezone = env('GOOGLE_TIMEZONE', 'Asia/Kathmandu');
    }

    public function createAuthUrl()
    {
        return $this->client->createAuthUrl();
    }

    public function handleGoogleCallback($code)
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);
        \Log::info($token);
        
        if (isset($token['error'])) {
            throw new \Exception('Failed to obtain token: ' . $token['error']);
        }

        $payload = $this->client->verifyIdToken($token['id_token']);
        
        if (!$payload) {
            throw new \Exception('Invalid ID token');
        }

        $email = $payload['email'];
        $this->client->setAccessToken($token['access_token']);
        $refreshToken = $token['refresh_token'] ?? null;
        $expirationTime = now()->addSeconds($token['expires_in'] ?? 3600);

        $oauth2Service = new GoogleOauth2($this->client);
        $userInfo = $oauth2Service->userinfo->get();
        $name = $userInfo->name;

        return User::updateOrCreate(['email' => $email], [
            'name' => $name,
            'google_token' => $token['access_token'],
            'google_refresh_token' => $refreshToken,
            'google_token_expires_at' => $expirationTime,
        ]);
    }

    public function refreshGoogleToken($user)
    {
        $refreshToken = $user->google_refresh_token;  // Get the stored refresh token

        if (!$refreshToken) { // Ensure the refresh token exists
            throw new \Exception('Refresh token not available.');
        }

        $this->client->refreshToken($refreshToken);  // Set the refresh token in the client

        try { // Get the new access token
            $newAccessToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);

            if (!isset($newAccessToken['access_token'])) {
                throw new \Exception('Failed to refresh access token. Google response: ' . json_encode($newAccessToken));
            }

            // $expiresIn = now()->addMinutes(1);
            $expiresIn = $newAccessToken['expires_in'] ?? 3600;
            $user->google_token = $newAccessToken['access_token'];
            $user->google_token_expires_at = now()->addSeconds($expiresIn);
            $user->save();

            return $newAccessToken['access_token'];
        } catch (\Exception $e) {
            \Log::error('Failed to refresh token: ' . $e->getMessage());
            throw new \Exception('Failed to refresh token: ' . $e->getMessage());
        }
    }

    public function eventList($user)
    {
        if ($this->isTokenExpired($user)) {
            $accessToken = $this->refreshGoogleToken($user);
        } else {
            $accessToken = $user->google_token;
        }

        $this->client->setAccessToken($accessToken);
        $calendarService = new GoogleCalendar($this->client);
        return $calendarService;
        
    }

    public function addEvent($user, $eventData)
    {
        $googleToken = $user->google_token;
        $this->client->setAccessToken($googleToken);

        $calendarService = new GoogleCalendar($this->client);
        $event = new GoogleCalendar\Event([
            'summary' => $eventData['summary'],
            'start' => ['date' => $eventData['start'], 'timeZone' => $this->timezone],
            'end' => ['date' => $eventData['end'], 'timeZone' => $this->timezone],
            'colorId' => $this->getColorId($eventData['category']),
            'extendedProperties' => [
                'private' => ['category' => $eventData['category']],
            ],
        ]);

        $createdEvent = $calendarService->events->insert('primary', $event);
        $getEvent = $calendarService->events->get('primary', $createdEvent->getId());
        return $getEvent;
    }

    public function updateEvent($user, $eventId, $eventData)
    {
        $googleToken = $user->google_token;
        $this->client->setAccessToken($googleToken);

        $calendarService = new GoogleCalendar($this->client);
        $event = $calendarService->events->get('primary', $eventId);

        $event->setSummary($eventData['summary'] ?? $event->getSummary());
        $event->setStart(new GoogleCalendar\EventDateTime([
            'date' => $eventData['start'],
            'timeZone' => $this->timezone,
        ]));
        $event->setEnd(new GoogleCalendar\EventDateTime([
            'date' => $eventData['end'],
            'timeZone' => $this->timezone,
        ]));
        $event->setColorId($this->getColorId($eventData['category']));

        if ($eventData['category']) {
            $extendedProperties = $event->getExtendedProperties() ?: new GoogleCalendar\EventExtendedProperties();
            $extendedProperties->setPrivate(['category' => $eventData['category']]);
            $event->setExtendedProperties($extendedProperties);
        }

        return $calendarService->events->update('primary', $eventId, $event);
    }

    public function deleteEvent($user, $eventId)
    {
        $googleToken = $user->google_token;
        $this->client->setAccessToken($googleToken);
        $calendarService = new GoogleCalendar($this->client);
        $calendarService->events->delete('primary', $eventId);
    }

    public function isTokenExpired($user)
    {
        return now()->greaterThan($user->google_token_expires_at);
    }

    private function getColorId($category)
    {
        switch ($category) {
            case 'personal':
                return '4'; // Red
            case 'business':
                return '9'; // Bold Blue
            case 'family':
                return '6'; // Orange
            case 'holiday':
                return '2'; // Green
            case 'etc':
                return '7'; // Turquoise
            default:
                return '1'; // Default to Blue
        }
    }
}
