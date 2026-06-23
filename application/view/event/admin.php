<div class="container event-page event-admin">
    <!-- Kopfbereich der nur fuer Administratoren erreichbaren Eventverwaltung. -->
    <div class="event-heading">
        <div>
            <span class="event-eyebrow">Administration</span>
            <h1>Eventverwaltung</h1>
            <p>Events erstellen, bearbeiten, löschen und Teilnehmerlisten einsehen.</p>
        </div>
        <a class="event-button event-button-secondary" href="<?php echo Config::get('URL'); ?>event/index">Besucheransicht</a>
    </div>

    <?php $this->renderFeedbackMessages(); ?>

    <!-- Sendet neue Eventdaten an EventController::save(). -->
    <section class="event-panel event-create-panel">
        <h2>Neues Event erstellen</h2>
        <form action="<?php echo Config::get('URL'); ?>event/save" method="post" class="event-form event-admin-form">
            <label>Titel<input type="text" name="event_title" maxlength="120" required /></label>
            <label>Datum und Uhrzeit<input type="datetime-local" name="event_date" required /></label>
            <label>Ort<input type="text" name="event_location" maxlength="255" required /></label>
            <label>Teilnehmerlimit<input type="number" name="event_max_participants" min="1" required /></label>
            <label class="event-form-wide">Beschreibung<textarea name="event_description" rows="4" required></textarea></label>
            <div class="event-form-wide"><input type="submit" value="Event erstellen" /></div>
        </form>
    </section>

    <!-- Jedes Event erhaelt ein eigenes Formular zum Bearbeiten und eine Teilnehmerliste. -->
    <div class="event-admin-list">
        <?php foreach ($this->events as $event) { ?>
            <?php
            // Holt die im Controller nach Event-ID gruppierten Teilnehmer; ohne Eintraege wird ein leeres Array verwendet.
            $eventParticipants = isset($this->participants[(int)$event->event_id]) ? $this->participants[(int)$event->event_id] : array();
            ?>
            <article class="event-panel event-admin-card">
                <div class="event-admin-card-head">
                    <div>
                        <span class="event-status"><?php echo (int)$event->participant_count; ?> / <?php echo (int)$event->event_max_participants; ?> Teilnehmer</span>
                        <h2><?php echo $this->encodeHTML($event->event_title); ?></h2>
                    </div>
                    <!-- Die Sicherheitsabfrage verhindert versehentliches Loeschen durch einen einzelnen Klick. -->
                    <form action="<?php echo Config::get('URL') . 'event/delete/' . (int)$event->event_id; ?>" method="post" onsubmit="return confirm('Event wirklich löschen?');">
                        <button type="submit" class="event-danger">Löschen</button>
                    </form>
                </div>

                <!-- Vorhandene Werte werden eingesetzt, damit der Admin nur die gewuenschten Felder aendern muss. -->
                <form action="<?php echo Config::get('URL'); ?>event/edit" method="post" class="event-form event-admin-form">
                    <input type="hidden" name="event_id" value="<?php echo (int)$event->event_id; ?>" />
                    <label>Titel<input type="text" name="event_title" maxlength="120" value="<?php echo $this->encodeHTML($event->event_title); ?>" required /></label>
                    <label>Datum und Uhrzeit<input type="datetime-local" name="event_date" value="<?php echo date('Y-m-d\TH:i', strtotime($event->event_date)); ?>" required /></label>
                    <label>Ort<input type="text" name="event_location" maxlength="255" value="<?php echo $this->encodeHTML($event->event_location); ?>" required /></label>
                    <label>Teilnehmerlimit<input type="number" name="event_max_participants" min="<?php echo max(1, (int)$event->participant_count); ?>" value="<?php echo (int)$event->event_max_participants; ?>" required /></label>
                    <label class="event-form-wide">Beschreibung<textarea name="event_description" rows="3" required><?php echo $this->encodeHTML($event->event_description); ?></textarea></label>
                    <div class="event-form-wide"><input type="submit" value="Änderungen speichern" /></div>
                </form>

                <!-- Das details-Element haelt lange Teilnehmerlisten standardmaessig platzsparend geschlossen. -->
                <details class="event-participants">
                    <summary>Teilnehmerliste (<?php echo count($eventParticipants); ?>)</summary>
                    <?php if ($eventParticipants) { ?>
                        <div class="event-table-wrap">
                            <table>
                                <thead><tr><th>Name</th><th>E-Mail</th><th>Angemeldet am</th></tr></thead>
                                <tbody>
                                <?php foreach ($eventParticipants as $participant) { ?>
                                    <tr>
                                        <td><?php echo $this->encodeHTML($participant->participant_name); ?></td>
                                        <td><?php echo $this->encodeHTML($participant->participant_email); ?></td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($participant->registration_created_at)); ?></td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php } else { ?>
                        <p>Noch keine Anmeldungen.</p>
                    <?php } ?>
                </details>
            </article>
        <?php } ?>
        <!-- Informiert den Admin, wenn noch kein Event zum Bearbeiten vorhanden ist. -->
        <?php if (!$this->events) { ?><div class="event-empty">Noch keine Events vorhanden.</div><?php } ?>
    </div>
</div>
