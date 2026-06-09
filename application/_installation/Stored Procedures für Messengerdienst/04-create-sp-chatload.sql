DELIMITER $$

DROP PROCEDURE IF EXISTS SP_CHAT_LOAD$$
CREATE PROCEDURE SP_CHAT_LOAD
(
    IN pCurrentUserId INT,
    IN pTargetUserId INT,
    IN pConversationType BOOLEAN
)
BEGIN

    DECLARE vConversationId INT;

    IF pConversationType = 0 THEN

        SELECT cu.conversation_id
        INTO vConversationId
        FROM conversation_users cu
        INNER JOIN conversations c
            ON c.id = cu.conversation_id
        WHERE cu.user_id IN (pCurrentUserId,pTargetUserId)
            AND c.type = 0
        GROUP BY cu.conversation_id
        HAVING COUNT(*) = 2
        LIMIT 1;

    ELSE

        SELECT id
        INTO vConversationId
        FROM conversations
        WHERE type = 1
        ORDER BY id
        LIMIT 1;

    END IF;

    UPDATE messages
    SET message_status = 1
    WHERE conversation_id = vConversationId
        AND sender_id <> pCurrentUserId;

    SELECT
        m.id,
        m.conversation_id,
        m.sender_id,
        u.user_name,
        m.message,
        m.message_status
    FROM messages m
    INNER JOIN users u
        ON u.user_id = m.sender_id
    WHERE m.conversation_id = vConversationId
    ORDER BY m.id;

END$$

DELIMITER ;