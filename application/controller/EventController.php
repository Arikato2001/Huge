<?php

class EventController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        // checkAdminAuthentication steht bewusst in save/edit/delete statt hier, weil register/unregister fuer Besucher erreichbar sein muessen.
    }

    public function save()
    {
        // Schuetzt das Speichern, weil nur Admins neue Events anlegen duerfen.
        Auth::checkAdminAuthentication();

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

    public function edit()
    {
        // Schuetzt das Bearbeiten, weil Eventverwaltung laut AP4 eine Admin-Funktion ist.
        Auth::checkAdminAuthentication();

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
        // Schuetzt das Loeschen, weil nur Admins Events entfernen duerfen.
        Auth::checkAdminAuthentication();

        // Loescht ein Event ueber das Model, damit auch die ID-Pruefung an einer Stelle bleibt.
        EventModel::deleteEvent($eventId);
        Redirect::to('admin');
    }

    public function register()
    {
        // Initialisiert die Session, damit eingeloggte User optional mit ihrer User-ID gespeichert werden koennen.
        Session::init();

        // Speichert eine Eventanmeldung mit Name und E-Mail und prueft dabei das Teilnehmerlimit im Model.
        EventModel::registerParticipant(
            Request::post('event_id'),
            Request::post('participant_name'),
            Request::post('participant_email'),
            $this->getCurrentUserId()
        );
        Redirect::to('index');
    }

    public function unregister()
    {
        // Initialisiert die Session, damit eingeloggte User bei der Abmeldung optional beruecksichtigt werden.
        Session::init();

        // Storniert eine Anmeldung anhand von Event-ID und E-Mail, damit Besucher ihre Buchung zuruecknehmen koennen.
        EventModel::unregisterParticipant(
            Request::post('event_id'),
            Request::post('participant_email'),
            $this->getCurrentUserId()
        );
        Redirect::to('index');
    }

    private function getCurrentUserId()
    {
        // Gibt die User-ID nur zurueck, wenn wirklich eine Anmeldung im System aktiv ist.
        return Session::userIsLoggedIn() ? Session::get('user_id') : null;
    }
}
