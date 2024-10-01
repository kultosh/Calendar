<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Oauth2 as GoogleOauth2; // Import the Google Oauth2 service
use App\Models\User;

class GoogleController extends Controller
{
    private $client;

    public function __construct()
    {
        $this->client = new GoogleClient();
        $this->client->setClientId(env('GOOGLE_CLIENT_ID'));
        $this->client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $this->client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
        $this->client->addScope(['https://www.googleapis.com/auth/calendar', 'email', 'profile']); // Include profile scope
        $this->client->setAccessType('offline'); // Ensure offline access to get the refresh token
        $this->client->setPrompt('consent'); // Force the prompt to ensure we get the refresh token
    }

    public function redirectToGoogle()
    {
        return response()->json(['url' => $this->client->createAuthUrl()]);
    }

    public function handleGoogleCallback(Request $request)
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($request->code);
        \Log::info($token);
        
        if (isset($token['error'])) {
            return response()->json(['error' => 'Failed to obtain token: ' . $token['error']], 401);
        }
        
        // Verify the ID token
        $payload = $this->client->verifyIdToken($token['id_token']);
        
        if ($payload) {
            // Retrieve the user's Google email
            $email = $payload['email'];

            // Set the access token to make a request to the Google Oauth2 API
            $this->client->setAccessToken($token['access_token']);
            $refreshToken = isset($token['refresh_token']) ? $token['refresh_token'] : null;

            // Calculate expiration time
            // $expirationTime = now()->addMinutes(1);
            $expirationTime = now()->addSeconds($token['expires_in'] ?? 3600); // Default to 1 hour if not set

            $oauth2Service = new GoogleOauth2($this->client);
            $userInfo = $oauth2Service->userinfo->get(); // Get user info from Google
            
            // Retrieve the user's name
            $name = $userInfo->name;

            // Update or create the user in the database
            $user = User::updateOrCreate(['email' => $email], [
                'name' => $name, // Store the user's name
                'google_token' => $token['access_token'],
                'google_refresh_token' => $refreshToken,   // Store the refresh token
                'google_token_expires_at' => $expirationTime, // Store the expiration time
            ]);
            
            return redirect('http://localhost:8080/auth?token=' . $user->createToken('auth_token')->plainTextToken);
        } else {
            // Handle error if the ID token is invalid
            return response()->json(['error' => 'Invalid ID token'], 401);
        }
    }

    public function refreshGoogleToken($user)
    {
        $client = new GoogleClient();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));

        // Get the stored refresh token
        $refreshToken = $user->google_refresh_token;

        // Ensure the refresh token exists
        if (!$refreshToken) {
            throw new \Exception('Refresh token not available.');
        }

        // Set the refresh token in the client
        $client->refreshToken($refreshToken);

        // Get the new access token
        try {
            $newAccessToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);

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


    public function getGoogleEvents(Request $request)
    {
        $user = $request->user();

        // Check if the user is authenticated
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        // Check if the access token has expired
        if ($this->isTokenExpired($user)) {
            // Refresh the token if it has expired
            $accessToken = $this->refreshGoogleToken($user);
        } else {
            $accessToken = $user->google_token;
        }

        $client = new GoogleClient();
        $client->setAccessToken($accessToken);

        $calendarService = new GoogleCalendar($client);
        if($request->isFilter=='true') {
            $filterList = $request->selectedFilterList;
            $events = [];
            foreach($filterList as $category) {
                $optParams = [
                    'fields' => 'items(id, summary, start, end, extendedProperties)',
                    'privateExtendedProperty' => "category=$category", // Filter by private extended property
                    'maxResults' => 10,
                    'orderBy' => 'startTime',
                    'singleEvents' => true,
                ];
                $events[]=$calendarService->events->listEvents('primary', $optParams)->getItems();
            }
            $events = collect($events)->collapse();
        } else {
            $events = $calendarService->events->listEvents('primary')->getItems();
        }
        
        $eventList = [];
        foreach ($events as $event) {
            $eventList[] = [
                'id' => $event->getId(),
                'title' => $event->getSummary(),
                'start' => $event->getStart()->date,
                'end' => $event->getEnd()->date,
                'extendedProperties' => $event->getExtendedProperties() ? $event->getExtendedProperties()->getPrivate() : null,
                'color' => $this->getFrontendBackgroundColour($event->getColorId()),
                'textColor' => $this->getFrontendTextColour($event->getColorId()),
                'description' => $event->getColorId(),
            ];
        }

        return response()->json($eventList);
    }

    public function isTokenExpired($user)
    {
        // Ensure we're checking the right property on the user object
        $expirationTime = $user->google_token_expires_at; // Adjusted to the correct property name
        
        // Check if the current time is greater than the expiration time
        return now()->greaterThan($expirationTime);

    }

    public function addGoogleEvent(Request $request)
    {
        try {
            $googleToken = $request->user()->google_token;
            $client = new GoogleClient();
            $client->setAccessToken($googleToken);

            $calendarService = new GoogleCalendar($client);
            $event = new GoogleCalendar\Event([
                'summary' => $request->summary,
                'start' => ['date' => $request->start, 'timeZone' => 'Asia/Kathmandu'],
                'end' => ['date' => $request->end, 'timeZone' => 'Asia/Kathmandu'],
                'colorId' => $this->getColorId($request->category),
                'extendedProperties' => [
                    'private' => [
                        'category' => $request->category,
                    ]
                ],
            ]);

            $calendarService->events->insert('primary', $event);

            return response()->json(['message' => 'Event created']);
        } catch (\Exception $error) {
            \Log::info($error->getMessage());
            dd($error->getMessage());
        }
    }

    public function updateGoogleEvent(Request $request, $eventId)
    {
        try {
            $googleToken = $request->user()->google_token;
            $client = new GoogleClient();
            $client->setAccessToken($googleToken);

            $calendarService = new GoogleCalendar($client);

            // Fetch the existing event
            $event = $calendarService->events->get('primary', $eventId);
            
            // Update the event properties based on request data
            $event->setSummary($request->summary ?? $event->getSummary());
            $event->setStart(new GoogleCalendar\EventDateTime([
                'date' => $request->start,
                'timeZone' => 'Asia/Kathmandu',
            ]));
            $event->setEnd(new GoogleCalendar\EventDateTime([
                'date' => $request->end,
                'timeZone' => 'Asia/Kathmandu',
            ]));

            $event->setColorId($this->getColorId($request->category));

            if ($request->category) {
                $extendedProperties = $event->getExtendedProperties() ?: new GoogleCalendar\EventExtendedProperties();
                $extendedProperties->setPrivate(['category' => $request->category]);
                $event->setExtendedProperties($extendedProperties);
            }

            $updatedEvent = $calendarService->events->update('primary', $eventId, $event);
            $data = [
                'id' => $updatedEvent->getId(),
                'title' => $updatedEvent->getSummary(),
                'start' => $updatedEvent->getStart()->date,
                'end' => $updatedEvent->getEnd()->date,
                'extendedProperties' => $updatedEvent->getExtendedProperties() ? $updatedEvent->getExtendedProperties()->getPrivate() : null,
                'color' => $this->getFrontendBackgroundColour($event->getColorId()),
                'textColor' => $this->getFrontendTextColour($event->getColorId()),
            ];

            return response()->json(['message' => 'Event updated successfully', 'event' => $data]);
        } catch (\Exception $error) {
            \Log::error($error->getMessage());
            return response()->json(['error' => 'Failed to update event'], 500);
        }
    }

    public function deleteGoogleEvent(Request $request, $eventId) {
        try {
            $googleToken = $request->user()->google_token;
            $client = new GoogleClient();
            $client->setAccessToken($googleToken);
    
            $calendarService = new GoogleCalendar($client);
    
            // Attempt to delete the event
            $calendarService->events->delete('primary', $eventId);
    
            return response()->json(['message' => 'Event Deleted Successfully'], 200);
        } catch (\Google_Service_Exception $e) {
            \Log::error('Google Service Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete event: ' . $e->getMessage()], 500);
        } catch (\Exception $error) {
            \Log::error('General Error: ' . $error->getMessage());
            return response()->json(['error' => 'Failed to delete event'], 500);
        }
    }

    private function getColorId($category) {
        switch($category) {
            case 'personal':
                return '4';  // Red
            case 'business':
                return '9';  // Bold blue
            case 'family':
                return '6';  // Orange
            case 'holiday':
                return '2';  // Green
            case 'etc':
                return '7';  // Turquoise
            default:
                return '1';  // Default to Blue
        }
    }

    private function getFrontendBackgroundColour($colorId) {
        switch($colorId) {
            case '2':
                return '#e0fcee'; //Holiday
            case '6':
                return '#fffaeb';  // Family
            case '4':
                return '#f9d1d800';  // Personal
            case '7':
                return '#dbfbfc';  // ETC
            default:
                return '#f4f0ff';  // Default to Blue
        }
    }
    
    private function getFrontendTextColour($colorId) {
        switch($colorId) {
            case '2':
                return '#00e381'; //Green
            case '6':
                return '#ffb66a';  // Yellow
            case '4':
                return '#ff6a86';  // Personal
            case '7':
                return '#00d6eb';  // Sky Blue
            default:
                return '#a35cf4';  // Default to Blue
        }
    }
}
