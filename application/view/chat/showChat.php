 <h1><?= ($this->user_chat ? ($this->user_chat->is_group ? htmlentities($this->user_chat->name) : 'Chat with ' . htmlentities($this->user_chat->user_name)) : 'Chat'); ?></h1>
    <div class="box">

        <!-- echo out the system feedback (error and success messages) -->
        <?php $this->renderFeedbackMessages(); ?>

        <?php if ($this->user_chat) { ?>
            <div>
                <section class="discussion">
                    <?php foreach ($this->user_chat->messages as $key => $message) {
                        $type = ($message->sender_id == Session::get('user_id') ? 'recipient' : 'sender');

                        $previous_message = isset($this->user_chat->messages[$key - 1]) ? $this->user_chat->messages[$key - 1] : null;
                        $next_message = isset($this->user_chat->messages[$key + 1]) ? $this->user_chat->messages[$key + 1] : null;

                        $previous_same_sender = $previous_message && $previous_message->sender_id == $message->sender_id;
                        $next_same_sender = $next_message && $next_message->sender_id == $message->sender_id;

                        $position = '';
                        if (!$previous_same_sender && $next_same_sender) {
                            $position = 'first';
                        } else if ($previous_same_sender && $next_same_sender) {
                            $position = 'middle';
                        } else if ($previous_same_sender && !$next_same_sender != $type) {
                            $position = 'last';
                        }
                    ?>
                        <div class="bubble <?= $type . ' ' . $position; ?>">
                        <?php if ($this->user_chat->is_group && $message->sender_id != Session::get('user_id')) { ?>
                                <span class="message-sender-name"><?= htmlentities($message->user_name); ?></span>
                            <?php } ?> 
                        <?= htmlentities($message->message); ?>
                        </div>
                    <?php } ?>

                </section>

                <form action="<?= Config::get('URL') . ($this->user_chat->is_group ? 'chat/saveNewGroupMessage' : 'chat/saveNewMessage/' . $this->user_chat->user_id); ?>" method="POST">                    <div class="input">
                        <input type="text" name="chat_message" placeholder="Chat here">
                        <button type="submit">Send</button>
                    </div>
                </form>
            </div>
        <?php } ?>

    </div>
</div>