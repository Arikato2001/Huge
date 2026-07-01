<?php
// Die Belegung entscheidet, ob das Anmeldeformular oder der Ausgebucht-Hinweis gezeigt wird.
$isFull = (int)$this->event->available_places <= 0;
?>
<div class="container event-page">
    <a class="event-back" href="<?php echo Config::get('URL'); ?>event/index">← Zur Eventübersicht</a>

    <?php $this->renderFeedbackMessages(); ?>

    <!-- Zeigt die wichtigsten Eventdaten und die aktuelle Belegung im Kopfbereich. -->
    <div class="event-detail">
        <main>
            <span class="event-eyebrow"><?php echo date('d.m.Y · H:i', strtotime($this->event->event_date)); ?> Uhr</span>
            <h1><?php echo $this->encodeHTML($this->event->event_title); ?></h1>
            <p class="event-detail-description"><?php echo nl2br($this->encodeHTML($this->event->event_description)); ?></p>
        </main>
        <aside class="event-facts">
            <div><span>Ort</span><strong><?php echo $this->encodeHTML($this->event->event_location); ?></strong></div>
            <div><span>Teilnehmer</span><strong><?php echo (int)$this->event->participant_count; ?> / <?php echo (int)$this->event->event_max_participants; ?></strong></div>
            <div><span>Verfügbar</span><strong><?php echo max(0, (int)$this->event->available_places); ?> Plätze</strong></div>
        </aside>
    </div>

    <!-- Anmeldung und Stornierung stehen als getrennte Formulare nebeneinander. -->
    <div class="event-form-grid">
        <section class="event-panel">
            <h2>Für das Event anmelden</h2>
            <!-- Bei vollem Teilnehmerlimit wird keine weitere Anmeldung angeboten. -->
            <?php if ($isFull) { ?>
                <p class="event-full-message">Dieses Event ist bereits ausgebucht.</p>
            <?php } else { ?>
                <p>Nach dem Absenden bekommst du eine E-Mail mit einem Bestätigungslink. Erst nach dem Klick ist dein Platz fix reserviert.</p>
                <!-- Die Event-ID wird versteckt mitgesendet, damit das Backend die Anmeldung zuordnen kann. -->
                <form action="<?php echo Config::get('URL'); ?>event/register" method="post" class="event-form">
                    <input type="hidden" name="event_id" value="<?php echo (int)$this->event->event_id; ?>" />
                    <label for="participant_name">Name</label>
                    <input id="participant_name" type="text" name="participant_name" maxlength="120" required />
                    <label for="participant_email">E-Mail-Adresse</label>
                    <input id="participant_email" type="email" name="participant_email" maxlength="254" required />
                    <input type="submit" value="Bestätigungs-E-Mail anfordern" />
                </form>
            <?php } ?>
        </section>

        <section class="event-panel event-panel-muted">
            <h2>Anmeldung stornieren</h2>
            <p>Verwende dieselbe E-Mail-Adresse wie bei deiner Anmeldung.</p>
            <!-- Die Stornierung identifiziert die vorhandene Anmeldung ueber Event-ID und E-Mail. -->
            <form action="<?php echo Config::get('URL'); ?>event/unregister" method="post" class="event-form">
                <input type="hidden" name="event_id" value="<?php echo (int)$this->event->event_id; ?>" />
                <label for="unregister_email">E-Mail-Adresse</label>
                <input id="unregister_email" type="email" name="participant_email" maxlength="254" required />
                <input type="submit" value="Anmeldung stornieren" />
            </form>
        </section>
    </div>
</div>
