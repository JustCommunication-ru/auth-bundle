<?php


namespace JustCommunication\AuthBundle\Exception;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Аналог use Symfony\Component\Security\Core\Exception\AccessDeniedException;
 * Используется для идентификации запрета доступа в Ajax протоколе
 */
class AccessDeniedAjaxException extends AccessDeniedException
{

}
