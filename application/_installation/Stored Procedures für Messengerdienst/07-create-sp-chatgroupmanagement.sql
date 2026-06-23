DELIMITER $$

DROP PROCEDURE IF EXISTS SP_CHAT_GROUP_MANAGEMENT$$
CREATE PROCEDURE SP_CHAT_GROUP_MANAGEMENT
(
    IN pUserId INT
)
BEGIN

    DECLARE vConversationId INT;

    SELECT id
    INTO vConversationId
    FROM conversations
    WHERE type = 1
    ORDER BY id
    LIMIT 1;

    IF vConversationId IS NULL THEN

        INSERT INTO conversations(type)
        VALUES(1);

        SET vConversationId = LAST_INSERT_ID();

    END IF;

    INSERT IGNORE INTO conversation_users
    (
        conversation_id,
        user_id
    )
    SELECT
        vConversationId,
        user_id
    FROM users;

    IF pUserId IS NOT NULL THEN

        INSERT IGNORE INTO conversation_users
        (
            conversation_id,
            user_id
        )
        VALUES
        (
            vConversationId,
            pUserId
        );

    END IF;

    SELECT vConversationId AS group_conversation_id;

END$$

DELIMITER ;
