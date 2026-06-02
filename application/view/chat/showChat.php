<head>
    <link rel="stylesheet" href="<?php echo Config::get('URL'); ?>css/chatdesign.css" />
</head>
<div class="container">
	<h1>ChatController/showChat/:id</h1>
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

                        $previous_type = null;
                        $next_type = null;

                        if ($previous_message) {
                            $previous_type = ($previous_message->sender_id == Session::get('user_id') ? 'recipient' : 'sender');
                        }

                        if ($next_message) {
                            $next_type = ($next_message->sender_id == Session::get('user_id') ? 'recipient' : 'sender');
                        }

                        $position = '';
                        if ($previous_type != $type && $next_type == $type) {
                            $position = 'first';
                        } else if ($previous_type == $type && $next_type == $type) {
                            $position = 'middle';
                        } else if ($previous_type == $type && $next_type != $type) {
                            $position = 'last';
                        }
                    ?>
                        <div class="bubble <?= $type . ' ' . $position; ?>">
                            <?= htmlentities($message->message); ?>
                        </div>
                    <?php } ?>

                </section>

                <form action="<?= Config::get('URL') . 'chat/saveNewMessage/' . $this->user_chat->user_id; ?>" method="POST">
                    <div class="input">
                        <input type="text" name="chat_message" placeholder="Chat here">
                        <button type="submit">Send</button>
                    </div>
                </form>
            </div>
        <?php } ?>

    </div>
</div>