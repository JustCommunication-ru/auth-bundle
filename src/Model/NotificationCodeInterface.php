<?php
namespace JustCommunication\AuthBundle\Model;

interface NotificationCodeInterface{

    public function getCode(): string;

    public function getTries(): int;

}
