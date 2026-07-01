<?php

class EventController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        // checkAdminAuthentication steht bewusst in save/edit/delete statt hier, weil register/unregister fuer Besucher erreichbar sein muessen.
    }

    public function index()
    {
        // Liest die optionalen Suchwerte aus der URL, damit Filter beim Neuladen sichtbar bleiben.
        $filters = array(
            'title' => trim((string)Request::get('title')),
            'date' => trim((string)Request::get('date')),
            'location' => trim((string)Request::get('location'))
        );

        // Laedt nur Events, die zu den ausgewaehlten Suchkriterien passen.
        $this->View->render('event/index', array(
            'events' => EventModel::searchEvents($filters['title'], $filters['date'], $filters['location']),
            'locations' => EventModel::getEventLocations(),
            'filters' => $filters
        ));
    }

    public function show($eventId = null)
    {
        // Holt genau das Event aus der URL, damit die Detailseite nur die ausgewaehlten Daten anzeigt.
        $event = EventModel::getEvent($eventId);

        // Leitet bei einer ungueltigen oder nicht vorhandenen ID kontrolliert zur Uebersicht zurueck.
        if (!$event) {
            Session::add('feedback_negative', 'Event wurde nicht gefunden.');
            Redirect::to('event/index');
            return;
        }

        // Stellt der Detailansicht das Event samt Teilnehmerzahl und freien Plaetzen bereit.
        $this->View->render('event/show', array(
            'event' => $event
        ));
    }

    public function admin()
    {
        // Der komplette Verwaltungsbereich darf nur von Administratoren geoeffnet werden.
        Auth::checkAdminAuthentication();

        // Laedt zuerst alle Events, die anschliessend in einzelnen Bearbeitungsformularen dargestellt werden.
        $events = EventModel::getAllEvents();
        $participants = array();

        // Ordnet jedem Event seine Teilnehmerliste anhand der Event-ID zu.
        // Dadurch kann die View die passende Liste direkt unter dem jeweiligen Event ausgeben.
        foreach ($events as $event) {
            $participants[(int)$event->event_id] = EventModel::getParticipantsByEvent($event->event_id);
        }

        // Uebergibt beide Datenbereiche gemeinsam an die geschuetzte Admin-Oberflaeche.
        $this->View->render('event/admin', array(
            'events' => $events,
            'participants' => $participants
        ));
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

        // Zeigt nach dem Speichern wieder die Verwaltung und dort die Rueckmeldung des Models an.
        Redirect::to('event/admin');
    }

    public function edit()
    {
        // Schuetzt das Bearbeiten, weil die Eventverwaltung eine Admin-Funktion ist.
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

        // Kehrt nach der Bearbeitung zur aktualisierten Eventliste zurueck.
        Redirect::to('event/admin');
    }

    public function delete($eventId)
    {
        // Schuetzt das Loeschen, weil nur Admins Events entfernen duerfen.
        Auth::checkAdminAuthentication();

        // Loescht ein Event ueber das Model, damit auch die ID-Pruefung an einer Stelle bleibt.
        EventModel::deleteEvent($eventId);

        // Laedt die Verwaltung neu, damit das geloeschte Event nicht mehr angezeigt wird.
        Redirect::to('event/admin');
    }

    public function register()
    {
        // Initialisiert die Session, damit eingeloggte User optional mit ihrer User-ID gespeichert werden koennen.
        Session::init();

        if (!Request::isPost()) {
            Redirect::to('event/index');
            return;
        }

        $eventId = Request::post('event_id');

        if (!ctype_digit((string)$eventId)) {
            Session::add('feedback_negative', 'Ungueltige Event-ID.');
            Redirect::to('event/index');
            return;
        }

        // Legt zuerst eine wartende Anmeldung an und versendet danach den Bestaetigungslink per E-Mail.
        EventModel::registerParticipant(
            $eventId,
            Request::post('participant_name'),
            Request::post('participant_email'),
            $this->getCurrentUserId()
        );

        // Bleibt nach der Aktion auf der Detailseite, damit freie Plaetze und Feedback sofort sichtbar sind.
        Redirect::to('event/show/' . $eventId);
    }

    public function confirm($registrationId, $token)
    {
        // Initialisiert die Session, damit die Rueckmeldung nach dem Redirect angezeigt werden kann.
        Session::init();

        // Bestaetigt die Anmeldung anhand der ID und des geheimen Tokens aus der E-Mail.
        $eventId = EventModel::confirmRegistration($registrationId, $token);

        // Bei erfolgreicher Zuordnung geht es zur passenden Detailseite, sonst zur Eventuebersicht.
        if ($eventId) {
            Redirect::to('event/show/' . $eventId);
            return;
        }

        Redirect::to('event/index');
    }

    public function unregister()
    {
        // Initialisiert die Session, damit eingeloggte User bei der Abmeldung optional beruecksichtigt werden.
        Session::init();
        $eventId = Request::post('event_id');

        if (!ctype_digit((string)$eventId)) {
            Session::add('feedback_negative', 'Ungueltige Event-ID.');
            Redirect::to('event/index');
            return;
        }

        // Storniert eine Anmeldung anhand von Event-ID und E-Mail, damit Besucher ihre Buchung zuruecknehmen koennen.
        EventModel::unregisterParticipant(
            $eventId,
            Request::post('participant_email'),
            $this->getCurrentUserId()
        );

        // Laedt dieselbe Detailseite nach der Stornierung mit dem aktualisierten Platzstand neu.
        Redirect::to('event/show/' . $eventId);
    }

    private function getCurrentUserId()
    {
        // Gibt die User-ID nur zurueck, wenn wirklich eine Anmeldung im System aktiv ist.
        return Session::userIsLoggedIn() ? Session::get('user_id') : null;
    }
}
