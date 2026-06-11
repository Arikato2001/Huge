<?php

class GalleryModel
{
    const MAX_IMAGE_SIZE = 10000000;

    public static function getOwnPictures()
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "SELECT id, name, size, downloads, OwnerID, Shared
                FROM files
                WHERE OwnerID = :owner_id
                ORDER BY id DESC";
        $query = $database->prepare($sql);
        $query->execute(array(':owner_id' => Session::get('user_id')));

        return $query->fetchAll();
    }

    public static function getPicture($id)
    {
        if (!ctype_digit((string)$id)) {
            return false;
        }

        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "SELECT id, name, size, downloads, OwnerID, Shared
                FROM files
                WHERE id = :id
                LIMIT 1";
        $query = $database->prepare($sql);
        $query->execute(array(':id' => (int)$id));

        return $query->fetch();
    }

    public static function userCanViewPicture($picture)
    {
        if (!$picture) {
            return false;
        }

        return ((int)$picture->OwnerID === (int)Session::get('user_id'));
    }

    public static function userOwnsPicture($picture)
    {
        return ($picture && (int)$picture->OwnerID === (int)Session::get('user_id'));
    }

    public static function createPicture()
    {
        if (!self::validateImageUpload()) {
            return false;
        }

        $userId = (int)Session::get('user_id');
        $targetDirectory = self::getUserPictureDirectory($userId);

        if (!self::ensureDirectoryExists($targetDirectory)) {
            Session::add('feedback_negative', 'Der private Bilderordner konnte nicht erstellt werden.');
            return false;
        }

        $imageData = getimagesize($_FILES['gallery_file']['tmp_name']);
        $extension = self::getExtensionByMimeType($imageData['mime']);
        $originalName = pathinfo($_FILES['gallery_file']['name'], PATHINFO_FILENAME);
        $safeName = self::sanitizeFileName($originalName);
        $fileName = $safeName . '-' . str_replace('.', '', uniqid('', true)) . '.' . $extension;
        $targetPath = $targetDirectory . $fileName;

        if (!move_uploaded_file($_FILES['gallery_file']['tmp_name'], $targetPath)) {
            Session::add('feedback_negative', 'Das Bild konnte nicht gespeichert werden.');
            return false;
        }

        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "INSERT INTO files (name, size, downloads, OwnerID, Shared)
                VALUES (:name, :size, 0, :owner_id, 0)";
        $query = $database->prepare($sql);
        $query->execute(array(
            ':name' => $fileName,
            ':size' => (int)$_FILES['gallery_file']['size'],
            ':owner_id' => $userId
        ));

        if ($query->rowCount() === 1) {
            Session::add('feedback_positive', 'Bild wurde hochgeladen.');
            return true;
        }

        @unlink($targetPath);
        Session::add('feedback_negative', 'Das Bild konnte nicht in der Datenbank gespeichert werden.');
        return false;
    }

    public static function deletePicture($id)
    {
        $picture = self::getPicture($id);

        if (!self::userOwnsPicture($picture)) {
            Session::add('feedback_negative', 'Dieses Bild gehoert nicht zu deinem Konto.');
            return false;
        }

        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "DELETE FROM files WHERE id = :id AND OwnerID = :owner_id LIMIT 1";
        $query = $database->prepare($sql);
        $query->execute(array(
            ':id' => (int)$picture->id,
            ':owner_id' => (int)Session::get('user_id')
        ));

        if ($query->rowCount() === 1) {
            @unlink(self::getPicturePath($picture));
            Session::add('feedback_positive', 'Bild wurde geloescht.');
            return true;
        }

        Session::add('feedback_negative', 'Bild konnte nicht geloescht werden.');
        return false;
    }

    public static function countDownload($id)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "UPDATE files SET downloads = downloads + 1 WHERE id = :id LIMIT 1";
        $query = $database->prepare($sql);
        $query->execute(array(':id' => (int)$id));
    }

    public static function outputPicture($picture, $asDownload = false)
    {
        if (!self::userCanViewPicture($picture)) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
            exit();
        }

        $path = self::getPicturePath($picture);

        if (!is_file($path)) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
            exit();
        }

        $imageData = getimagesize($path);
        $mimeType = $imageData ? $imageData['mime'] : 'application/octet-stream';

        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');

        if ($asDownload) {
            header('Content-Disposition: attachment; filename="' . basename($picture->name) . '"');
        }

        readfile($path);
        exit();
    }

    public static function getPicturePath($picture)
    {
        return self::getUserPictureDirectory((int)$picture->OwnerID) . basename($picture->name);
    }

    private static function validateImageUpload()
    {
        if (!isset($_FILES['gallery_file']) || $_FILES['gallery_file']['error'] !== UPLOAD_ERR_OK) {
            Session::add('feedback_negative', 'Bitte waehle ein Bild zum Hochladen aus.');
            return false;
        }

        if ($_FILES['gallery_file']['size'] > self::MAX_IMAGE_SIZE) {
            Session::add('feedback_negative', 'Das Bild ist zu gross. Maximal erlaubt sind 10 MB.');
            return false;
        }

        $imageData = getimagesize($_FILES['gallery_file']['tmp_name']);

        if (!$imageData || !in_array($imageData['mime'], array('image/jpeg', 'image/png', 'image/gif'))) {
            Session::add('feedback_negative', 'Erlaubt sind nur JPG-, PNG- und GIF-Bilder.');
            return false;
        }

        return true;
    }

    private static function getUserPictureDirectory($userId)
    {
        return Config::get('PATH_USERPICTURES') . (int)$userId . '/';
    }

    private static function ensureDirectoryExists($directory)
    {
        if (is_dir($directory)) {
            return is_writable($directory);
        }

        return mkdir($directory, 0755, true);
    }

    private static function sanitizeFileName($name)
    {
        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9_-]+/', '-', $name);
        $name = trim($name, '-');

        return $name ? $name : 'bild';
    }

    private static function getExtensionByMimeType($mimeType)
    {
        switch ($mimeType) {
            case 'image/png':
                return 'png';
            case 'image/gif':
                return 'gif';
            default:
                return 'jpg';
        }
    }
}
