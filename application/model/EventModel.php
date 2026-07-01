<?php

class EventModel
{
    public static function getAllEvents()
    {
        // Verwendet dieselbe Abfrage wie die Suche, jedoch ohne eingeschraenkte Suchkriterien.
        return self::searchEvents();
    }

    public static function searchEvents($title = '', $date = '', $location = '')
    {
        // Baut die Bedingungen einzeln auf, damit leere Filter keinen Einfluss auf das Ergebnis haben.
        $database = DatabaseFactory::getFactory()->getConnection();
        $conditions = array();
        $parameters = array();

        if (trim((string)$title) !== '') {
            $conditions[] = 'e.event_title LIKE :title';
            $parameters[':title'] = '%' . trim($title) . '%';
        }

        // Akzeptiert nur das Format des HTML-Datumsfeldes und ignoriert manipulierte Datumswerte.
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$date)) {
            $conditions[] = 'DATE(e.event_date) = :event_date';
            $parameters[':event_date'] = $date;
        }

        if (trim((string)$location) !== '') {
            $conditions[] = 'e.event_location = :location';
            $parameters[':location'] = trim($location);
        }

        // Verbindet mehrere aktive Filter mit AND, damit alle Kriterien gleichzeitig zutreffen muessen.
        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $sql = "SELECT e.event_id, e.event_title, e.event_description, e.event_date, e.event_location,
                       e.event_max_participants, e.event_created_at,
                       COALESCE(SUM(CASE WHEN r.registration_confirmed = 1 THEN 1 ELSE 0 END), 0) AS participant_count,
                       COALESCE(SUM(CASE WHEN r.registration_confirmed = 0 AND r.confirmation_expires_at >= NOW() THEN 1 ELSE 0 END), 0) AS pending_count,
                       (e.event_max_participants - COALESCE(SUM(CASE WHEN r.registration_confirmed = 1 THEN 1 ELSE 0 END), 0)) AS available_places
                FROM events e
                LEFT JOIN event_registrations r ON r.event_id = e.event_id
                " . $where . "
                GROUP BY e.event_id, e.event_title, e.event_description, e.event_date, e.event_location,
                         e.event_max_participants, e.event_created_at
                ORDER BY e.event_date ASC, e.event_id DESC";
        $query = $database->prepare($sql);
        $query->execute($parameters);

        return $query->fetchAll();
    }

    public static function getEventLocations()
    {
        // Liefert jeden gespeicherten Ort nur einmal fuer die Auswahlliste des Filters.
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "SELECT DISTINCT event_location
                FROM events
                WHERE event_location <> ''
                ORDER BY event_location ASC";
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
                       COALESCE(SUM(CASE WHEN r.registration_confirmed = 1 THEN 1 ELSE 0 END), 0) AS participant_count,
                       COALESCE(SUM(CASE WHEN r.registration_confirmed = 0 AND r.confirmation_expires_at >= NOW() THEN 1 ELSE 0 END), 0) AS pending_count,
                       (e.event_max_participants - COALESCE(SUM(CASE WHEN r.registration_confirmed = 1 THEN 1 ELSE 0 END), 0)) AS available_places
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
                       r.registration_confirmed, r.confirmation_expires_at, r.registration_confirmed_at,
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

        // Entfernt alte, nie bestaetigte Anmeldungen, damit abgelaufene Links keine Plaetze blockieren.
        self::deleteExpiredRegistrations($database);

        // Sperrt das Event waehrend der Limitpruefung, damit parallele Anmeldungen keine Ueberbuchung erzeugen.
        $eventQuery = $database->prepare("SELECT event_id, event_title, event_date, event_max_participants FROM events WHERE event_id = :event_id LIMIT 1 FOR UPDATE");
        $eventQuery->execute(array(':event_id' => (int)$eventId));
        $event = $eventQuery->fetch();

        if (!$event) {
            $database->rollBack();
            Session::add('feedback_negative', 'Event wurde nicht gefunden.');
            return false;
        }

        // Nur bestaetigte Anmeldungen belegen einen Platz. Beim Bestaetigen wird das Limit erneut geprueft.
        $countQuery = $database->prepare("SELECT COUNT(*) AS participant_count
                                          FROM event_registrations
                                          WHERE event_id = :event_id
                                            AND registration_confirmed = 1");
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

        // Der rohe Token kommt nur in den Link; in der Datenbank liegt nur der Hash.
        $confirmationToken = bin2hex(random_bytes(32));
        $confirmationTokenHash = hash('sha256', $confirmationToken);

        $sql = "INSERT INTO event_registrations
                    (event_id, user_id, participant_name, participant_email, registration_confirmed,
                     registration_confirmation_token, confirmation_expires_at)
                VALUES
                    (:event_id, :user_id, :participant_name, :participant_email, 0,
                     :confirmation_token, DATE_ADD(NOW(), INTERVAL 24 HOUR))";
        $query = $database->prepare($sql);
        $query->execute(array(
            ':event_id' => (int)$eventId,
            ':user_id' => $userId ? (int)$userId : null,
            ':participant_name' => trim($participantName),
            ':participant_email' => trim($participantEmail),
            ':confirmation_token' => $confirmationTokenHash
        ));

        if ($query->rowCount() === 1) {
            $registrationId = (int)$database->lastInsertId();
            $database->commit();

            // Die Mail wird erst nach dem Speichern gesendet, damit der Link auf einen echten Datensatz zeigt.
            if (self::sendConfirmationMail($registrationId, $confirmationToken, $participantEmail, $participantName, $event)) {
                Session::add('feedback_positive', self::getConfirmationMailFeedback($participantEmail));
                return true;
            }

            // Wenn Mercury oder SMTP nicht erreichbar ist, wird die wartende Anmeldung wieder entfernt.
            self::deleteRegistrationById($registrationId);
            Session::add('feedback_negative', 'Bestaetigungs-E-Mail konnte nicht gesendet werden. Bitte pruefe Mercury.');
            return false;
        }

        $database->rollBack();
        Session::add('feedback_negative', 'Anmeldung konnte nicht gespeichert werden.');
        return false;
    }

    public static function confirmRegistration($registrationId, $token)
    {
        // Prueft Linkbestandteile, bevor eine Datenbanktransaktion gestartet wird.
        if (!ctype_digit((string)$registrationId) || !preg_match('/^[a-f0-9]{64}$/', (string)$token)) {
            Session::add('feedback_negative', 'Bestaetigungslink ist ungueltig.');
            return false;
        }

        $database = DatabaseFactory::getFactory()->getConnection();
        $database->beginTransaction();

        // Entfernt alte ausstehende Anmeldungen, damit abgelaufene Links nicht mehr bestaetigt werden koennen.
        self::deleteExpiredRegistrations($database);

        // Sperrt die Anmeldung, damit derselbe Link nicht parallel doppelt bestaetigt werden kann.
        $registrationQuery = $database->prepare(
            "SELECT r.registration_id, r.event_id, r.registration_confirmed,
                    r.registration_confirmation_token, r.confirmation_expires_at,
                    e.event_max_participants
             FROM event_registrations r
             INNER JOIN events e ON e.event_id = r.event_id
             WHERE r.registration_id = :registration_id
             LIMIT 1
             FOR UPDATE"
        );
        $registrationQuery->execute(array(':registration_id' => (int)$registrationId));
        $registration = $registrationQuery->fetch();

        if (!$registration) {
            $database->rollBack();
            Session::add('feedback_negative', 'Anmeldung wurde nicht gefunden oder ist abgelaufen.');
            return false;
        }

        if ((int)$registration->registration_confirmed === 1) {
            $database->commit();
            Session::add('feedback_positive', 'Diese Anmeldung wurde bereits bestaetigt.');
            return (int)$registration->event_id;
        }

        // Vergleicht den Hash aus dem Link mit dem gespeicherten Hash, ohne den echten Token zu speichern.
        $tokenHash = hash('sha256', $token);
        if (!hash_equals((string)$registration->registration_confirmation_token, $tokenHash)) {
            $database->rollBack();
            Session::add('feedback_negative', 'Bestaetigungslink ist ungueltig.');
            return false;
        }

        if (strtotime($registration->confirmation_expires_at) < time()) {
            $database->rollBack();
            Session::add('feedback_negative', 'Bestaetigungslink ist abgelaufen. Bitte melde dich erneut an.');
            return false;
        }

        // Prueft beim Klick nochmals das Limit, weil mehrere Personen gleichzeitig einen Link erhalten koennen.
        $countQuery = $database->prepare(
            "SELECT COUNT(*) AS confirmed_count
             FROM event_registrations
             WHERE event_id = :event_id
               AND registration_confirmed = 1"
        );
        $countQuery->execute(array(':event_id' => (int)$registration->event_id));
        $confirmedCount = (int)$countQuery->fetch()->confirmed_count;

        if ($confirmedCount >= (int)$registration->event_max_participants) {
            $database->rollBack();
            Session::add('feedback_negative', 'Event ist bereits voll. Deine Anmeldung konnte nicht bestaetigt werden.');
            return (int)$registration->event_id;
        }

        // Nach erfolgreicher Bestaetigung wird der Token geloescht, damit der Link nicht erneut nutzbar ist.
        $updateQuery = $database->prepare(
            "UPDATE event_registrations
             SET registration_confirmed = 1,
                 registration_confirmation_token = NULL,
                 confirmation_expires_at = NULL,
                 registration_confirmed_at = NOW()
             WHERE registration_id = :registration_id
             LIMIT 1"
        );
        $updateQuery->execute(array(':registration_id' => (int)$registrationId));

        $database->commit();
        Session::add('feedback_positive', 'Deine Event-Anmeldung wurde bestaetigt.');
        return (int)$registration->event_id;
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

    private static function deleteExpiredRegistrations($database)
    {
        // Abgelaufene, nicht bestaetigte Anmeldungen werden geloescht, damit Plaetze wieder frei werden.
        $query = $database->prepare(
            "DELETE FROM event_registrations
             WHERE registration_confirmed = 0
               AND confirmation_expires_at < NOW()"
        );
        $query->execute();
    }

    private static function deleteRegistrationById($registrationId)
    {
        // Wird genutzt, wenn zwar gespeichert wurde, aber keine Bestaetigungs-E-Mail versendet werden konnte.
        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare("DELETE FROM event_registrations WHERE registration_id = :registration_id LIMIT 1");
        $query->execute(array(':registration_id' => (int)$registrationId));
    }

    private static function sendConfirmationMail($registrationId, $token, $participantEmail, $participantName, $event)
    {
        // Der Link zeigt auf den lokalen Controller und enthaelt ID plus geheimen Token.
        $confirmationLink = Config::get('URL')
            . Config::get('EMAIL_EVENT_CONFIRMATION_URL') . '/'
            . (int)$registrationId . '/'
            . $token;

        $body = Config::get('EMAIL_EVENT_CONFIRMATION_CONTENT') . $confirmationLink . "\n\n"
              . "Event: " . $event->event_title . "\n"
              . "Datum: " . date('d.m.Y H:i', strtotime($event->event_date)) . " Uhr\n"
              . "Name: " . trim($participantName) . "\n\n"
              . "Der Link ist 24 Stunden gueltig.";

        $mail = new Mail();
        return $mail->sendMail(
            trim($participantEmail),
            Config::get('EMAIL_EVENT_CONFIRMATION_FROM_EMAIL'),
            Config::get('EMAIL_EVENT_CONFIRMATION_FROM_NAME'),
            Config::get('EMAIL_EVENT_CONFIRMATION_SUBJECT'),
            $body
        );
    }

    private static function getConfirmationMailFeedback($participantEmail)
    {
        $feedback = 'Bitte bestaetige deine Anmeldung ueber den Link in deiner E-Mail.';
        $emailParts = explode('@', trim((string)$participantEmail));

        if (count($emailParts) === 2 && strtolower($emailParts[1]) === 'event.local') {
            $feedback .= ' In Mercury findest du die Mail im lokalen Postfach "' . $emailParts[0] . '".';
        }

        return $feedback;
    }

    private static function validateRegistration($eventId, $participantName, $participantEmail)
    {
        // Validiert die Pflichtdaten, damit Name, E-Mail und Event-ID vor dem Insert stimmen.
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

        if (!self::localMailboxExists($participantEmail)) {
            Session::add('feedback_negative', 'Ungueltige E-Mail-Adresse. Bitte nutze ein vorhandenes Mercury-Postfach mit @' . Config::get('EMAIL_LOCAL_DOMAIN') . '.');
            return false;
        }

        return true;
    }

    private static function localMailboxExists($participantEmail)
    {
        $emailParts = explode('@', trim((string)$participantEmail));
        if (count($emailParts) !== 2) {
            return false;
        }

        $localDomain = strtolower((string)Config::get('EMAIL_LOCAL_DOMAIN'));
        $emailDomain = strtolower($emailParts[1]);

        if ($localDomain === '' || $emailDomain !== $localDomain) {
            return false;
        }

        $mailboxBasePath = rtrim((string)Config::get('EMAIL_LOCAL_MAILBOX_PATH'), '/\\') . DIRECTORY_SEPARATOR;
        if (!is_dir($mailboxBasePath)) {
            return false;
        }

        foreach (scandir($mailboxBasePath) as $mailbox) {
            if ($mailbox !== '.' && $mailbox !== '..' && strcasecmp($mailbox, $emailParts[0]) === 0) {
                return is_dir($mailboxBasePath . $mailbox);
            }
        }

        return false;
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
