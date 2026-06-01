<?php

/**
 * Class DBFactory
 *
 * Verwendung:
 * $database = DBFactory::getFactory()->getConnection();
 *
 * Diese Version verwendet mysqli statt PDO.
 */
class DBFactoryMySQLI
{
    private static $factory;
    private $database;
    /**
     * Singleton Factory holen
     */
    public static function getFactory()
    {
        if (!self::$factory) {
            self::$factory = new DBFactoryMySQLI();
        }

        return self::$factory;
    }

    /**
     * mysqli Verbindung holen
     */
    public function getConnection()
    {
        if (!$this->database) {
            try {
                $this->database = new mysqli(
                    Config::get('DB_HOST'),
                    Config::get('DB_USER'),
                    Config::get('DB_PASS'),
                    Config::get('DB_NAME'),
                    Config::get('DB_PORT')
                );
                /**
                 * Verbindung prüfen
                 */
                if ($this->database->connect_error) {
                    throw new Exception($this->database->connect_error);
                }
                /**
                 * Charset setzen
                 */
                $this->database->set_charset(Config::get('DB_CHARSET'));

            } catch (Exception $e) {

                echo 'Database connection can not be established. Please try again later.<br>';
                echo 'Error: ' . $e->getMessage();

                exit;
            }
        }
        return $this->database;
    }
}