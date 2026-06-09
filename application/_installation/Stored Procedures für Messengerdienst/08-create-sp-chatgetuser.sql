DELIMITER $$

CREATE PROCEDURE SP_CHAT_GET_USER
(
    IN pUserId INT
)
BEGIN

    SELECT
        user_id,
        user_name,
        user_email,
        user_active,
        user_has_avatar,
        user_deleted
    FROM users
    WHERE user_id = pUserId;

END$$

DELIMITER ;