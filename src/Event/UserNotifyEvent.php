<?php

namespace JustCommunication\AuthBundle\Event;

use JustCommunication\AuthBundle\Entity\User;
use JustCommunication\AuthBundle\Model\NotificationCodeInterface;
use Symfony\Contracts\EventDispatcher\Event;

class UserNotifyEvent extends Event
{

    /**
     * @param User $user
     * @param string $message
     * @param NotificationCodeInterface|null $notificationCode - по умолчанию код может отсутстовать, но если есть, его характеристики могут повлиять я на характер отправки
     */
    public function __construct(private User $user, private string $message, private ?NotificationCodeInterface $notificationCode=null)
    {
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param mixed $message
     * @return UserNotifyEvent
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return UserNotifyEvent
     */
    public function setUser(User $user): UserNotifyEvent
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return NotificationCodeInterface|null
     */
    public function getNotificationCode(): ?NotificationCodeInterface
    {
        return $this->notificationCode;
    }

    /**
     * @param NotificationCodeInterface|null $notificationCode
     * @return UserNotifyEvent
     */
    public function setNotificationCode(?NotificationCodeInterface $notificationCode): UserNotifyEvent
    {
        $this->notificationCode = $notificationCode;
        return $this;
    }



}