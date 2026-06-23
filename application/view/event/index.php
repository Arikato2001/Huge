<div class="container event-page">
    <!-- Kopfbereich der oeffentlichen Eventuebersicht. -->
    <div class="event-heading">
        <div>
            <span class="event-eyebrow">Event Management</span>
            <h1>Events entdecken</h1>
            <p>Alle Termine, Details und freien Plätze auf einen Blick.</p>
        </div>
        <!-- Nur Administratoren bekommen hier den direkten Wechsel zur Verwaltung angeboten. -->
        <?php if (Session::userIsLoggedIn() && Session::get('user_account_type') == 7) { ?>
            <a class="event-button event-button-secondary" href="<?php echo Config::get('URL'); ?>event/admin">Eventverwaltung</a>
        <?php } ?>
    </div>

    <!-- Gibt Erfolgs- oder Fehlermeldungen aus vorherigen Aktionen aus. -->
    <?php $this->renderFeedbackMessages(); ?>

    <!-- Erstellt fuer jedes vorhandene Event eine eigene Vorschaukarte. -->
    <?php if ($this->events) { ?>
        <div class="event-grid">
            <?php foreach ($this->events as $event) { ?>
                <?php
                // Steuert den Text und die Farbe des Belegungsstatus in der Karte.
                $isFull = (int)$event->available_places <= 0;
                ?>
                <article class="event-card">
                    <div class="event-card-date">
                        <strong><?php echo date('d', strtotime($event->event_date)); ?></strong>
                        <span><?php echo date('m.Y', strtotime($event->event_date)); ?></span>
                    </div>
                    <div class="event-card-content">
                        <span class="event-status <?php echo $isFull ? 'is-full' : ''; ?>">
                            <?php echo $isFull ? 'Ausgebucht' : (int)$event->available_places . ' Plätze frei'; ?>
                        </span>
                        <!-- Benutzereingaben werden vor der HTML-Ausgabe gegen XSS codiert. -->
                        <h2><?php echo $this->encodeHTML($event->event_title); ?></h2>
                        <p class="event-meta"><?php echo date('d.m.Y, H:i', strtotime($event->event_date)); ?> Uhr · <?php echo $this->encodeHTML($event->event_location); ?></p>
                        <p><?php echo $this->encodeHTML($event->event_description); ?></p>
                        <a class="event-button" href="<?php echo Config::get('URL') . 'event/show/' . (int)$event->event_id; ?>">Details ansehen</a>
                    </div>
                </article>
            <?php } ?>
        </div>
    <?php } else { ?>
        <!-- Dieser Platzhalter verhindert eine leere Seite, solange noch keine Events existieren. -->
        <div class="event-empty">Aktuell sind keine Events eingetragen.</div>
    <?php } ?>
</div>
