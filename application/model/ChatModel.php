<?php

/**
 * ChatModel
 * Handles all the chat stuff
 */
class ChatModel
{
    /**
     * @param int $user_id The user's id
     * @return array The chat with a user
     */
    public static function getUsersWitchChat($user_id)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "SELECT user_id, user_name, user_email, user_active, user_has_avatar, user_deleted
                FROM users WHERE user_id = :user_id";
        $query = $database->prepare($sql);
        $query->execute(array(':user_id' => $user_id));
        $chat = $query->fetch();

        $chat->messages = array();

        $sql = "SELECT cu.conversation_id FROM conversation_users cu
                INNER JOIN conversations c ON c.ID = cu.conversation_id
                WHERE cu.user_id IN (:user_id, :current_user_id)
                    AND c.type = 0
                GROUP BY cu.conversation_id
                HAVING COUNT(*) = 2
                LIMIT 1";
        $query = $database->prepare($sql);
        $query->execute(array(
            ':user_id' => $user_id,
            ':current_user_id' => Session::get('user_id')
        ));
        $conversation = $query->fetch();

        if ($conversation) {
            $sql = "UPDATE messages
                    SET message_status = 1
                    WHERE conversation_id = :conversation_id
                        AND sender_id != :current_user_id";
            $query = $database->prepare($sql);
            $query->execute(array(
                ':conversation_id' => $conversation->conversation_id,
                ':current_user_id' => Session::get('user_id')
            ));

            $sql = "SELECT ID, conversation_id, sender_id, message, message_status
                    FROM messages
                    WHERE conversation_id = :conversation_id
                    ORDER BY ID ASC";
            $query = $database->prepare($sql);
            $query->execute(array(':conversation_id' => $conversation->conversation_id));
            $chat->messages = $query->fetchAll();
        }

        return $chat;
    }

    /**
     * @param int $user_id The other user's id
     * @return int Number of unseen messages from this user
     */
    public static function getUnseenMessagesCount($user_id)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "SELECT COUNT(m.ID) AS unseen_messages
                FROM messages m
                INNER JOIN conversations c ON c.ID = m.conversation_id
                INNER JOIN conversation_users cu1 ON cu1.conversation_id = c.ID
                INNER JOIN conversation_users cu2 ON cu2.conversation_id = c.ID
                WHERE c.type = 0
                    AND cu1.user_id = :member_user_id
                    AND cu2.user_id = :current_user_id
                    AND m.sender_id = :sender_user_id
                    AND m.message_status = 0";
        $query = $database->prepare($sql);
        $query->execute(array(
            ':member_user_id' => $user_id,
            ':current_user_id' => Session::get('user_id'),
            ':sender_user_id' => $user_id
        ));
        $result = $query->fetch();

        return $result->unseen_messages;
    }

    /**
     * @param $user_id int id the the user
     * @return 
     */
    public static function saveMessage($user_id, $message)
    {   
        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "SELECT cu.conversation_id FROM conversation_users cu
                INNER JOIN conversations c ON c.ID = cu.conversation_id
                WHERE cu.user_id IN (:user_id, :current_user_id)
                    AND c.type = 0
                GROUP BY cu.conversation_id
                HAVING COUNT(*) = 2
                LIMIT 1";
        $query = $database->prepare($sql);
        $query->execute(array(
            ':user_id' => $user_id,
            ':current_user_id' => Session::get('user_id')
        ));
        $conversation = $query->fetch();

        if (!$conversation) {
            $sql = "INSERT INTO conversations (type) VALUES (0)";
            $query = $database->prepare($sql);
            $query->execute();

            $conversation_id = $database->lastInsertId();

            $sql = "INSERT INTO conversation_users (conversation_id, user_id)
                    VALUES (:conversation_id_1, :user_id_1),
                           (:conversation_id_2, :user_id_2)";
            $query = $database->prepare($sql);
            $query->execute(array(
                ':conversation_id_1' => $conversation_id,
                ':user_id_1' => Session::get('user_id'),
                ':conversation_id_2' => $conversation_id,
                ':user_id_2' => $user_id
            ));
        } else {
            $conversation_id = $conversation->conversation_id;
        }

        $sql = "INSERT INTO messages (conversation_id, sender_id, message)
                VALUES (:conversation_id, :sender_id, :message)";
        $query = $database->prepare($sql);

        $query->execute(array(
            ':conversation_id' => $conversation_id,
            ':sender_id' => Session::get('user_id'),
            ':message' => $message
        ));
    }
}