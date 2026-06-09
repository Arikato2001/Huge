<?php

class ChatModel
{
    const DEFAULT_GROUP_NAME = 'Group Chat (Everyone)';

    /**
     * Lädt einen privaten Chat.
     */
    public static function getUsersWitchChat($user_id)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        // Benutzer laden
        $sql = "CALL SP_CHAT_GET_USER(:user_id)";
        $query = $database->prepare($sql);

        $query->execute(array(
            ':user_id' => $user_id
        ));

        $chat = $query->fetch();

        if (!$chat) {
            return false;
        }

        $query->closeCursor();

        $chat->is_group = false;

        // Chat laden
        $sql = "CALL SP_CHAT_LOAD(
                    :current_user_id,
                    :target_user_id,
                    0
                )";

        $query = $database->prepare($sql);

        $query->execute(array(  
            ':current_user_id' => Session::get('user_id'),
            ':target_user_id' => $user_id
        ));

        $chat->messages = $query->fetchAll();

        $query->closeCursor();

        return $chat;
    }

    /**
     * Lädt den Gruppenchat.
     */
    public static function getDefaultGroupChat()
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "CALL SP_CHAT_LOAD(
                    :current_user_id,
                    NULL,
                    1
                )";

        $query = $database->prepare($sql);

        $query->execute(array(
            ':current_user_id' => Session::get('user_id')
        ));

        $chat = new stdClass();
        $chat->is_group = true;
        $chat->name = self::DEFAULT_GROUP_NAME;
        $chat->messages = $query->fetchAll();

        $query->closeCursor();

        return $chat;
    }

    /**
     * Übersicht Gruppenchat.
     */
    public static function getDefaultGroupChatOverview()
    {
        $group_chat = new stdClass();

        $group_chat->name = self::DEFAULT_GROUP_NAME;
        $group_chat->unseen_messages =
            self::getDefaultGroupUnreadMessagesCount();

        return $group_chat;
    }

    /**
     * Ungelesene Nachrichten eines privaten Chats.
     */
    public static function getUnseenMessagesCount($user_id)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "CALL SP_CHAT_UNREAD(
                    :current_user_id,
                    :target_user_id,
                    0
                )";

        $query = $database->prepare($sql);

        $query->execute(array(
            ':current_user_id' => Session::get('user_id'),
            ':target_user_id' => $user_id
        ));

        $result = $query->fetch();

        $query->closeCursor();

        return $result->unread_messages;
    }

    /**
     * Ungelesene Nachrichten des Gruppenchats.
     */
    public static function getDefaultGroupUnreadMessagesCount()
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "CALL SP_CHAT_UNREAD(
                    :current_user_id,
                    NULL,
                    1
                )";

        $query = $database->prepare($sql);

        $query->execute(array(
            ':current_user_id' => Session::get('user_id')
        ));

        $result = $query->fetch();

        $query->closeCursor();

        return $result->unread_messages;
    }

    /**
     * Private Nachricht speichern.
     */
    public static function saveMessage($user_id, $message)
    {
        if (!$message) {
            return;
        }

        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "CALL SP_CHAT_SEND(
                    :current_user_id,
                    :target_user_id,
                    :message,
                    0
                )";

        $query = $database->prepare($sql);

        $query->execute(array(
            ':current_user_id' => Session::get('user_id'),
            ':target_user_id' => $user_id,
            ':message' => $message
        ));

        $query->closeCursor();
    }

    /**
     * Gruppennachricht speichern.
     */
    public static function saveNewGroupMessage($message)
    {
        if (!$message) {
            return;
        }

        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "CALL SP_CHAT_SEND(
                    :current_user_id,
                    NULL,
                    :message,
                    1
                )";

        $query = $database->prepare($sql);

        $query->execute(array(
            ':current_user_id' => Session::get('user_id'),
            ':message' => $message
        ));

        $query->closeCursor();
    }

    /**
     * Benutzer zur Standardgruppe hinzufügen.
     */
    public static function addUserToDefaultGroup($user_id)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "CALL SP_CHAT_GROUP_MANAGEMENT(:user_id)";

        $query = $database->prepare($sql);

        $query->execute(array(
            ':user_id' => $user_id
        ));

        $query->closeCursor();
    }
}