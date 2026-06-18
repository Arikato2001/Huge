<?php

class EventModel
{
    public static function getAllEvents()
    {
        // Liest alle Events mit Teilnehmeranzahl, damit die Verwaltung freie Plaetze anzeigen kann.
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "SELECT e.event_id, e.event_title, e.event_description, e.event_date, e.event_location,
                       e.event_max_participants, e.event_created_at,
                       COUNT(r.registration_id) AS participant_count,
                       (e.event_max_participants - COUNT(r.registration_id)) AS available_places
                FROM events e
                LEFT JOIN event_registrations r ON r.event_id = e.event_id
                GROUP BY e.event_id, e.event_title, e.event_description, e.event_date, e.event_location,
                         e.event_max_participants, e.event_created_at
                ORDER BY e.event_date ASC, e.event_id DESC";
        $query = $database->prepare($sql);
        $query->execute();

        return $query->fetchAll();
    }

    public static function getEvent($eventId)
    {
        // Prueft die ID vor der Datenbankabfrage, damit nur echte numerische Event-IDs verarbeitet werden.
        if (!ctype_digit((string)$eventId)) {
            return false;
        }

        // Laedt das Event inklusive Belegung, damit Bearbeiten und Limitpruefung dieselbe Datenbasis nutzen.
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "SELECT e.event_id, e.event_title, e.event_description, e.event_date, e.event_location,
                       e.event_max_participants,
                       COUNT(r.registration_id) AS participant_count,
                       (e.event_max_participants - COUNT(r.registration_id)) AS available_places
                FROM events e
                LEFT JOIN event_registrations r ON r.event_id = e.event_id
                WHERE e.event_id = :event_id
                GROUP BY e.event_id, e.event_title, e.event_description, e.event_date, e.event_location,
                         e.event_max_participants
                LIMIT 1";
        $query = $database->prepare($sql);
        $query->execute(array(':event_id' => (int)$eventId));

        return $query->fetch();
    }

    public static function getParticipantsByEvent($eventId)
    {
        // Prueft die Event-ID, damit Teilnehmerlisten nur fuer gueltige Events abgefragt werden.
        if (!ctype_digit((string)$eventId)) {
            return array();
        }

        // Liefert gespeicherte Teilnehmerdaten, damit Admins spaeter die Eventbelegung nachvollziehen koennen.
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "SELECT r.registration_id, r.participant_name, r.participant_email,
                       r.registration_created_at, u.user_id, u.user_name
                FROM event_registrations r
                LEFT JOIN users u ON u.user_id = r.user_id
                WHERE r.event_id = :event_id
                ORDER BY r.registration_created_at ASC";
        $query = $database->prepare($sql);
        $query->execute(array(':event_id' => (int)$eventId));

        return $query->fetchAll();
    }

    public static function createEvent($title, $description, $eventDate, $location, $maxParticipants)
    {
        // Bricht vor dem Speichern ab, wenn Pflichtfelder fehlen oder das Teilnehmerlimit ungueltig ist.
        if (!self::validateEvent($title, $description, $eventDate, $location, $maxParticipants)) {
            return false;
        }

        // Normalisiert das Datum vor dem Insert, damit MySQL immer ein einheitliches DATETIME-Format bekommt.
        $eventDate = self::formatEventDate($eventDate);
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "INSERT INTO events (event_title, event_description, event_date, event_location, event_max_participants, event_created_by)
                VALUES (:title, :description, :event_date, :location, :max_participants, :created_by)";
        $query = $database->prepare($sql);
        $query->execute(array(
            ':title' => trim($title),
            ':description' => trim($description),
            ':event_date' => trim($eventDate),
            ':location' => trim($location),
            ':max_participants' => (int)$maxParticipants,
            ':created_by' => (int)Session::get('user_id')
        ));

        // Positive Rueckmeldung wird nur gesetzt, wenn genau ein Datensatz angelegt wurde.
        if ($query->rowCount() === 1) {
            Session::add('feedback_positive', 'Event wurde erstellt.');
            return true;
        }

        Session::add('feedback_negative', 'Event konnte nicht erstellt werden.');
        return false;
    }

    public static function updateEvent($eventId, $title, $description, $eventDate, $location, $maxParticipants)
    {
        // Laedt das bestehende Event zuerst, damit nur vorhandene Events aktualisiert werden.
        $event = self::getEvent($eventId);
        if (!$event || !self::validateEvent($title, $description, $eventDate, $location, $maxParticipants)) {
            return false;
        }

        // Verhindert ein Teilnehmerlimit, das kleiner als die bereits gespeicherten Anmeldungen ist.
        if ((int)$maxParticipants < (int)$event->participant_count) {
            Session::add('feedback_negative', 'Maximale Teilnehmerzahl ist kleiner als die aktuellen Anmeldungen.');
            return false;
        }

        // Normalisiert das Datum auch beim Update, damit alte und neue Werte gleich gespeichert werden.
        $eventDate = self::formatEventDate($eventDate);
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "UPDATE events
                SET event_title = :title,
                    event_description = :description,
                    event_date = :event_date,
                    event_location = :location,
                    event_max_participants = :max_participants
                WHERE event_id = :event_id
                LIMIT 1";
        $query = $database->prepare($sql);
        $query->execute(array(
            ':event_id' => (int)$eventId,
            ':title' => trim($title),
            ':description' => trim($description),
            ':event_date' => trim($eventDate),
            ':location' => trim($location),
            ':max_participants' => (int)$maxParticipants
        ));

        // Nach erfolgreichem SQL-Aufruf bekommt der Admin eine klare Speicher-Rueckmeldung.
        Session::add('feedback_positive', 'Event wurde gespeichert.');
        return true;
    }

    public static function registerParticipant($eventId, $participantName, $participantEmail, $userId = null)
    {
        // Prueft die Teilnehmerdaten vor der Anmeldung, damit nur vollstaendige Anmeldungen gespeichert werden.
        if (!self::validateRegistration($eventId, $participantName, $participantEmail)) {
            return false;
        }

        $database = DatabaseFactory::getFactory()->getConnection();
        $database->beginTransaction();

        // Sperrt das Event waehrend der Limitpruefung, damit parallele Anmeldungen keine Ueberbuchung erzeugen.
        $eventQuery = $database->prepare("SELECT event_max_participants FROM events WHERE event_id = :event_id LIMIT 1 FOR UPDATE");
        $eventQuery->execute(array(':event_id' => (int)$eventId));
        $event = $eventQuery->fetch();

        if (!$event) {
            $database->rollBack();
            Session::add('feedback_negative', 'Event wurde nicht gefunden.');
            return false;
        }

        // Zaehlt aktuelle Anmeldungen innerhalb der Transaktion, damit das Limit auf dem neuesten Stand ist.
        $countQuery = $database->prepare("SELECT COUNT(*) AS participant_count FROM event_registrations WHERE event_id = :event_id");
        $countQuery->execute(array(':event_id' => (int)$eventId));
        $participantCount = (int)$countQuery->fetch()->participant_count;

        if ($participantCount >= (int)$event->event_max_participants) {
            $database->rollBack();
            Session::add('feedback_negative', 'Event ist bereits voll.');
            return false;
        }

        // Verhindert doppelte Anmeldungen mit derselben E-Mail-Adresse fuer dasselbe Event.
        if (self::registrationExists($eventId, $participantEmail, $userId)) {
            $database->rollBack();
            Session::add('feedback_negative', 'Diese Anmeldung existiert bereits.');
            return false;
        }

        $sql = "INSERT INTO event_registrations (event_id, user_id, participant_name, participant_email)
                VALUES (:event_id, :user_id, :participant_name, :participant_email)";
        $query = $database->prepare($sql);
        $query->execute(array(
            ':event_id' => (int)$eventId,
            ':user_id' => $userId ? (int)$userId : null,
            ':participant_name' => trim($participantName),
            ':participant_email' => trim($participantEmail)
        ));

        if ($query->rowCount() === 1) {
            $database->commit();
            Session::add('feedback_positive', 'Anmeldung wurde gespeichert.');
            return true;
        }

        $database->rollBack();
        Session::add('feedback_negative', 'Anmeldung konnte nicht gespeichert werden.');
        return false;
    }

    public static function unregisterParticipant($eventId, $participantEmail, $userId = null)
    {
        // Prueft Event-ID und E-Mail, damit nur gezielte Abmeldungen verarbeitet werden.
        if (!ctype_digit((string)$eventId) || !filter_var($participantEmail, FILTER_VALIDATE_EMAIL)) {
            Session::add('feedback_negative', 'Ungueltige Abmeldedaten.');
            return false;
        }

        // Loescht die Anmeldung per E-Mail und optionaler User-ID, damit Besucher und eingeloggte User abmelden koennen.
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "DELETE FROM event_registrations
                WHERE event_id = :event_id
                  AND participant_email = :participant_email";
        $parameters = array(
            ':event_id' => (int)$eventId,
            ':participant_email' => trim($participantEmail)
        );

        if ($userId) {
            $sql .= " AND (user_id = :user_id OR user_id IS NULL)";
            $parameters[':user_id'] = (int)$userId;
        }

        $sql .= " LIMIT 1";
        $query = $database->prepare($sql);
        $query->execute($parameters);

        if ($query->rowCount() === 1) {
            Session::add('feedback_positive', 'Anmeldung wurde storniert.');
            return true;
        }

        Session::add('feedback_negative', 'Anmeldung konnte nicht gefunden werden.');
        return false;
    }

    public static function deleteEvent($eventId)
    {
        // Prueft die ID vor dem Loeschen, damit keine ungueltigen Werte an SQL uebergeben werden.
        if (!ctype_digit((string)$eventId)) {
            Session::add('feedback_negative', 'Ungueltige Event-ID.');
            return false;
        }

        // Loescht das Event; zugehoerige Anmeldungen werden per Foreign Key automatisch entfernt.
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "DELETE FROM events WHERE event_id = :event_id LIMIT 1";
        $query = $database->prepare($sql);
        $query->execute(array(':event_id' => (int)$eventId));

        // Erfolg wird nur gemeldet, wenn wirklich genau ein Event geloescht wurde.
        if ($query->rowCount() === 1) {
            Session::add('feedback_positive', 'Event wurde geloescht.');
            return true;
        }

        Session::add('feedback_negative', 'Event konnte nicht geloescht werden.');
        return false;
    }

    private static function formatEventDate($eventDate)
    {
        // Wandelt gueltige Datumsangaben in das MySQL-DATETIME-Format um.
        return date('Y-m-d H:i:s', strtotime($eventDate));
    }

    private static function registrationExists($eventId, $participantEmail, $userId = null)
    {
        // Prueft vorhandene Anmeldungen, damit E-Mail oder eingeloggter User nicht doppelt gebucht werden.
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "SELECT registration_id
                FROM event_registrations
                WHERE event_id = :event_id
                  AND (participant_email = :participant_email";
        $parameters = array(
            ':event_id' => (int)$eventId,
            ':participant_email' => trim($participantEmail)
        );

        if ($userId) {
            $sql .= " OR user_id = :user_id";
            $parameters[':user_id'] = (int)$userId;
        }

        $sql .= ") LIMIT 1";
        $query = $database->prepare($sql);
        $query->execute($parameters);

        return (bool)$query->fetch();
    }

    private static function validateRegistration($eventId, $participantName, $participantEmail)
    {
        // Validiert die AP5-Pflichtdaten, damit Name, E-Mail und Event-ID vor dem Insert stimmen.
        if (!ctype_digit((string)$eventId)) {
            Session::add('feedback_negative', 'Ungueltige Event-ID.');
            return false;
        }

        if (!trim((string)$participantName)) {
            Session::add('feedback_negative', 'Bitte gib einen Namen ein.');
            return false;
        }

        if (!filter_var($participantEmail, FILTER_VALIDATE_EMAIL)) {
            Session::add('feedback_negative', 'Bitte gib eine gueltige E-Mail-Adresse ein.');
            return false;
        }

        return true;
    }

    private static function validateEvent($title, $description, $eventDate, $location, $maxParticipants)
    {
        // Validiert alle Pflichtangaben zentral, damit Create und Update dieselben Regeln verwenden.
        if (!trim((string)$title)) {
            Session::add('feedback_negative', 'Bitte gib einen Eventtitel ein.');
            return false;
        }

        if (!trim((string)$description)) {
            Session::add('feedback_negative', 'Bitte gib eine Eventbeschreibung ein.');
            return false;
        }

        if (!trim((string)$eventDate) || !strtotime($eventDate)) {
            Session::add('feedback_negative', 'Bitte gib ein gueltiges Eventdatum ein.');
            return false;
        }

        if (!trim((string)$location)) {
            Session::add('feedback_negative', 'Bitte gib einen Eventort ein.');
            return false;
        }

        if ((int)$maxParticipants < 1) {
            Session::add('feedback_negative', 'Maximale Teilnehmerzahl muss mindestens 1 sein.');
            return false;
        }

        return true;
    }
}
