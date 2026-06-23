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

    <!-- Die Filter werden als GET-Parameter gesendet, damit die Suche verlinkt und zurueckgesetzt werden kann. -->
    <form action="<?php echo Config::get('URL'); ?>event/index" method="get" class="event-search">
        <label class="event-search-title">
            Eventtitel
            <input type="search" name="title" value="<?php echo $this->encodeHTML($this->filters['title']); ?>" placeholder="Titel suchen ..." />
        </label>
        <label>
            Datum
            <input type="date" name="date" value="<?php echo $this->encodeHTML($this->filters['date']); ?>" />
        </label>
        <label>
            Ort
            <select name="location">
                <option value="">Alle Orte</option>
                <?php foreach ($this->locations as $location) { ?>
                    <option value="<?php echo $this->encodeHTML($location->event_location); ?>"<?php if ($this->filters['location'] === $location->event_location) { echo ' selected'; } ?>>
                        <?php echo $this->encodeHTML($location->event_location); ?>
                    </option>
                <?php } ?>
            </select>
        </label>
        <div class="event-search-actions">
            <button type="submit">Ergebnisse anzeigen</button>
            <a href="<?php echo Config::get('URL'); ?>event/index">Zuruecksetzen</a>
        </div>
    </form>

    <!-- Zeigt, wie viele Events nach Anwendung der Filter uebrig bleiben. -->
    <p class="event-result-count"><?php echo count($this->events); ?> Event<?php echo count($this->events) === 1 ? '' : 's'; ?> gefunden</p>

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
        <!-- Unterscheidet zwischen einer erfolglosen Suche und einer komplett leeren Eventliste. -->
        <?php $hasFilters = $this->filters['title'] !== '' || $this->filters['date'] !== '' || $this->filters['location'] !== ''; ?>
        <div class="event-empty">
            <?php echo $hasFilters ? 'Keine Events entsprechen den ausgewaehlten Filtern.' : 'Aktuell sind keine Events eingetragen.'; ?>
        </div>
    <?php } ?>
</div>
