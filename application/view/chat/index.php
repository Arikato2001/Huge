<div class="container">
    <h1>ChatController/index</h1>
    <div class="box">

        <!-- echo out the system feedback (error and success messages) -->
        <?php $this->renderFeedbackMessages(); ?>

        <div>
            <table class="overview-table js-data-table display">
                <thead>
                <tr>
                    <td>Avatar</td>
                    <td>Username</td>
                    <td>Chat</td>
                </tr>
                </thead>
                   <tr class="chat-group-row active">
                    <td></td>
                    <td><?= htmlentities($this->group_chat->name); ?></td>
                    <td>
                        <a class="chat-link" href="<?= Config::get('URL'); ?>chat/showGroupChat">
                            Chat
                            <?php if ($this->group_chat->unseen_messages > 0) { ?>
                                <span class="chat-unread-count"><?= $this->group_chat->unseen_messages; ?></span>
                            <?php } ?>
                        </a>
                    </td>
                </tr>
                <?php foreach ($this->users as $user) { 
                    if (Session::get("user_id") !== $user->user_id) {?>
                    <tr class="<?= ($user->user_active == 0 ? 'inactive' : 'active'); ?>">
                        <td class="avatar">
                            <?php if (isset($user->user_avatar_link)) { ?>
                                <img src="<?= $user->user_avatar_link; ?>" />
                            <?php } ?>
                        </td>
                        <td><?= $user->user_name; ?></td>
                        <td>
                            <a class="chat-link" href="<?= Config::get('URL') . 'chat/showChat/' . $user->user_id; ?>">
                                Chat
                                <?php if ($user->unseen_messages > 0) { ?>
                                    <span class="chat-unread-count"><?= $user->unseen_messages; ?></span>
                                <?php } ?>
                            </a>
                        </td>
                    </tr>
                <?php } } ?>
            </table>
        </div>
    </div>
</div>
