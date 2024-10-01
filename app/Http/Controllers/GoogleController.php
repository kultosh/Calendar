<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GoogleCalendarService;

class GoogleController extends Controller
{
    private $googleCalendarService;

    public function __construct(GoogleCalendarService $googleCalendarService)
    {
        $this->googleCalendarService = $googleCalendarService;
    }

    public function redirectToGoogle()
    {
        return response()->json(['url' => $this->googleCalendarService->createAuthUrl()]);
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $user = $this->googleCalendarService->handleGoogleCallback($request->code);
            return redirect(env('FRONTEND_APP_URL').'/auth?token=' . $user->createToken('auth_token')->plainTextToken);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        }
    }

    public function getGoogleEvents(Request $request)
    {
        $user = $request->user();

        // Check if the user is authenticated
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $calendarService = $this->googleCalendarService->eventList($user);

        if($request->isFilter=='true') {
            $filterList = $request->selectedFilterList;
            $events = [];
            foreach($filterList as $category) {
                $optParams = [
                    'fields' => 'items(id, summary, start, end, colorId, extendedProperties)',
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
            $eventList[] = $this->formatEvent($event);
        }

        return response()->json($eventList);
    }

    public function addGoogleEvent(Request $request)
    {
        try {
            $storedEvent = $this->googleCalendarService->addEvent($request->user(), $request->only(['summary', 'start', 'end', 'category']));
            return response()->json(['message' => 'Event created', 'event' => $this->formatEvent($storedEvent)]);
        } catch (\Exception $error) {
            \Log::info($error->getMessage());
        }
    }

    public function updateGoogleEvent(Request $request, $eventId)
    {
        try {
            $updatedEvent = $this->googleCalendarService->updateEvent($request->user(), $eventId, $request->only(['summary', 'start', 'end', 'category']));
            return response()->json(['message' => 'Event updated successfully', 'event' => $this->formatEvent($updatedEvent)]);
        } catch (\Exception $error) {
            \Log::error($error->getMessage());
            return response()->json(['error' => 'Failed to update event'], 500);
        }
    }

    public function deleteGoogleEvent(Request $request, $eventId) {
        try {
            $this->googleCalendarService->deleteEvent($request->user(), $eventId);
            return response()->json(['message' => 'Event Deleted Successfully'], 200);
        } catch (\Exception $error) {
            \Log::error($error->getMessage());
            return response()->json(['error' => 'Failed to delete event'], 500);
        }
    }

    private function formatEvent($data) {
        return [
            'id' => $data->getId(),
            'title' => $data->getSummary(),
            'start' => $data->getStart()->date,
            'end' => $data->getEnd()->date,
            'extendedProperties' => $data->getExtendedProperties() ? $data->getExtendedProperties()->getPrivate() : null,
            'color' => $this->getEventBackgroundColour($data->getColorId()),
            'textColor' => $this->getEventTextColour($data->getColorId()),
        ];
    }

    private function getEventBackgroundColour($colorId) {
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
    
    private function getEventTextColour($colorId) {
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
