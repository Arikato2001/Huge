<?php

class EventController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        // Schuetzt die gesamte Eventverwaltung, weil AP4 nur Admins das Erstellen, Bearbeiten und Loeschen erlaubt.
        Auth::checkAdminAuthentication();
    }

    public function createSave()
    {
        // Uebergibt die Formulardaten an das Model, damit die eigentliche Speicherlogik zentral bleibt.
        EventModel::createEvent(
            Request::post('event_title'),
            Request::post('event_description'),
            Request::post('event_date'),
            Request::post('event_location'),
            Request::post('event_max_participants')
        );
        Redirect::to('admin');
    }

    public function editSave()
    {
        // Aktualisiert ein bestehendes Event ueber das Model, damit Validierung und SQL nicht im Controller liegen.
        EventModel::updateEvent(
            Request::post('event_id'),
            Request::post('event_title'),
            Request::post('event_description'),
            Request::post('event_date'),
            Request::post('event_location'),
            Request::post('event_max_participants')
        );
        Redirect::to('admin');
    }

    public function delete($eventId)
    {
        // Loescht ein Event ueber das Model, damit auch die ID-Pruefung an einer Stelle bleibt.
        EventModel::deleteEvent($eventId);
        Redirect::to('admin');
    }
}
