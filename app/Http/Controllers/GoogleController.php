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
    }

    public function redirectToGoogle()
    {
        return response()->json(['url' => $this->client->createAuthUrl()]);
    }

    public function handleGoogleCallback(Request $request)
    {
        // Exchange the authorization code for an access token
        $token = $this->client->fetchAccessTokenWithAuthCode($request->code);
        
        // Verify the ID token
        $payload = $this->client->verifyIdToken($token['id_token']);
        
        if ($payload) {
            // Retrieve the user's Google email
            $email = $payload['email'];

            // Set the access token to make a request to the Google Oauth2 API
            $this->client->setAccessToken($token['access_token']);
            $oauth2Service = new GoogleOauth2($this->client);
            $userInfo = $oauth2Service->userinfo->get(); // Get user info from Google
            
            // Retrieve the user's name
            $name = $userInfo->name;

            // Update or create the user in the database
            $user = User::updateOrCreate(['email' => $email], [
                'name' => $name, // Store the user's name
                'google_token' => $token['access_token'],
            ]);

            // Return the Sanctum token for the frontend to use
            // return response()->json(['token' => $user->createToken('auth_token')->plainTextToken]);
            return redirect('http://localhost:8080/auth?token=' . $user->createToken('auth_token')->plainTextToken);
        } else {
            // Handle error if the ID token is invalid
            return response()->json(['error' => 'Invalid ID token'], 401);
        }
    }

    public function getGoogleEvents(Request $request)
    {
        $googleToken = $request->user()->google_token;

        $client = new GoogleClient();
        $client->setAccessToken($googleToken);

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
                'color' => '#00CFE8'
            ];
        }

        return response()->json($eventList);
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
}
