<?php

class ChatController extends Controller
{
    /**
     * Construct this object by extending the basic Controller class
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * This method controls what happens when you move to /overview/index in your app.
     * Shows a list of all users.
     */
    public function index()
    {
        $users = UserModel::getPublicProfilesOfAllUsers();

        foreach ($users as $user) {
            $user->unseen_messages = ChatModel::getUnseenMessagesCount($user->user_id);
        }

        $this->View->render('chat/index', array(
            'users' => $users)
        );
    }

        /**
     * This method controls what happens when you move to /overview/showChat in your app.
     * Shows the chat with other users
     * @param $user_id int id the the user
     */
    public function showChat($user_id)
    {
        if (isset($user_id)) {
            $this->View->render('chat/showChat', array(
                'user_chat' => ChatModel::getUsersWitchChat($user_id))
            );
        } else {
            Redirect::home();
        }
    }

        /**
     * This method controls what happens when you move to /overview/showChat in your app.
     * Saves the chat with other users
     * @param $user_id int id the the user
     */
    public function saveNewMessage($user_id)
    {   
        $message = Request::post('chat_message');
        ChatModel::saveMessage($user_id, $message);

        Redirect::to('chat/showChat/' . $user_id);
    }
}