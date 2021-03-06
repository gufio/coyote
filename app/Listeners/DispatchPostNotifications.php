<?php

namespace Coyote\Listeners;

use Coyote\Events\PostWasSaved;
use Coyote\Notifications\Post\ChangedNotification;
use Coyote\Notifications\Post\SubmittedNotification;
use Coyote\Notifications\Post\UserMentionedNotification;
use Coyote\Post;
use Coyote\Repositories\Contracts\UserRepositoryInterface as UserRepository;
use Coyote\Services\Parser\Helpers\Login as LoginHelper;
use Coyote\User;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchPostNotifications implements ShouldQueue
{
    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @var UserRepository
     */
    private $user;

    /**
     * @param Dispatcher $dispatcher
     * @param UserRepository $user
     */
    public function __construct(Dispatcher $dispatcher, UserRepository $user)
    {
        $this->dispatcher = $dispatcher;
        $this->user = $user;
    }

    /**
     * Handle the event.
     *
     * @param  PostWasSaved  $event
     * @return void
     */
    public function handle(PostWasSaved $event)
    {
        $post = $event->post;
        $user = $event->post->user;
        $topic = $event->post->topic;

        if ($event->wasRecentlyCreated) {
            $subscribers = $topic->subscribers()->with('user.notificationSettings')->get()->pluck('user')->exceptUser($user);
            $notification = (new SubmittedNotification($user, $post))->setSender($this->getSender($post));

            $this->dispatcher->send($subscribers, $notification);

            $this->sendUserMentionedNotification($post, $user, $subscribers);
        } else {
            $subscribers = $post->subscribers()->with('user')->get()->pluck('user')->exceptUser($user);

            $this->dispatcher->send($subscribers, new ChangedNotification($user, $post));

            $this->sendUserMentionedNotification($post, $user, $subscribers);
        }
    }

    /**
     * @param Post $post
     * @param User $user
     * @param array $subscribers
     */
    private function sendUserMentionedNotification(Post $post, User $user, $subscribers): void
    {
        // get id of users that were mentioned in the text
        $usersId = (new LoginHelper())->grab($post->html);

        if (!empty($usersId)) {
            $this->dispatcher->send(
                $this->user->findMany($usersId)->exceptUser($user)->exceptUsers($subscribers),
                (new UserMentionedNotification($user, $post))->setSender($this->getSender($post))
            );
        }
    }

    /**
     * @param Post $post
     * @return string
     */
    private function getSender(Post $post): string
    {
        return $post->user ? $post->user->name : $post->user_name;
    }
}
