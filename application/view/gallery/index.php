<div class="container gallery-page">
    <h1><?php echo $this->encodeHTML($this->headline); ?></h1>

    <?php $this->renderFeedbackMessages(); ?>

    <?php if ($this->show_upload) { ?>
        <div class="box">
            <form method="post" action="<?php echo Config::get('URL'); ?>gallery/upload" enctype="multipart/form-data" class="gallery-upload">
                <label for="gallery_file">Bild hochladen</label>
                <input type="file" id="gallery_file" name="gallery_file" accept="image/jpeg,image/png,image/gif" required />
                <input type="submit" value="Upload" autocomplete="off" />
            </form>
        </div>
    <?php } ?>

    <?php if ($this->pictures) { ?>
        <div class="gallery-grid">
            <?php foreach ($this->pictures as $picture) { ?>
                <figure class="gallery-item">
                    <a href="<?php echo Config::get('URL') . 'gallery/image/' . (int)$picture->id; ?>" class="gallery-zoom">
                        <img src="<?php echo Config::get('URL') . 'gallery/image/' . (int)$picture->id; ?>" alt="<?php echo $this->encodeHTML($picture->name); ?>" />
                    </a>
                    <figcaption>
                        <strong><?php echo $this->encodeHTML($picture->name); ?></strong>
                        <span><?php echo number_format((int)$picture->size / 1024, 1); ?> KB</span>
                        <span><?php echo (int)$picture->downloads; ?> Downloads</span>
                    </figcaption>
                    <div class="gallery-actions">
                        <a href="<?php echo Config::get('URL') . 'gallery/download/' . (int)$picture->id; ?>">Download</a>
                        <?php if ($this->show_upload) { ?>
                            <a href="<?php echo Config::get('URL') . 'gallery/delete/' . (int)$picture->id; ?>">Loeschen</a>
                        <?php } ?>
                    </div>
                </figure>
            <?php } ?>
        </div>
    <?php } else { ?>
        <div class="box">Noch keine Bilder vorhanden.</div>
    <?php } ?>
</div>
