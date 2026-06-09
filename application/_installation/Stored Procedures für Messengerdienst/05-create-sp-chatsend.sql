DELIMITER $$

CREATE PROCEDURE SP_CHAT_SEND
(
    IN pCurrentUserId INT,
    IN pTargetUserId INT,
    IN pMessage TEXT,
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

        IF vConversationId IS NULL THEN

            INSERT INTO conversations(type)
            VALUES(0);

            SET vConversationId = LAST_INSERT_ID();

            INSERT INTO conversation_users
            (
                conversation_id,
                user_id
            )
            VALUES
            (vConversationId,pCurrentUserId),
            (vConversationId,pTargetUserId);

        END IF;

    ELSE

        SELECT id
        INTO vConversationId
        FROM conversations
        WHERE type = 1
        ORDER BY id
        LIMIT 1;

    END IF;

    INSERT INTO messages
    (
        conversation_id,
        sender_id,
        message,
        message_status
    )
    VALUES
    (
        vConversationId,
        pCurrentUserId,
        pMessage,
        0
    );

END$$

DELIMITER ;