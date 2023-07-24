# Auth Bundle

Пакет должен был называться SecurityBundle, но произошел конфликт имен с Symfony/SecurityBundle.

Представляет из себя готовое решение по регистрации/авторизации через код подтверждения с телефона с формами для входа, таблицами хранения данных и прочего. 

## Установка 
`composer require justcommunication/auth-bundle`

## Требования
Для полноценной работы потребуется настроить конфигурацию хост проекта и подписчика на события для отправки сообщений.

## Подключение

В `.env` (`.env.local`) добавить константы:
```
SECURITY_LOGIN_ROUTE_REDIRECT=app_index # название роута на который произойдер редирект после успешной авторизации

SECURITY_AUTH_CODE_TIMEOUT=300 # срок действия кода для авторизации (в секундах)
SECURITY_AUTH_CODE_DELAY=10 # раз в столько секунд можно запрашивать код для регистрации повторно
SECURITY_AUTH_CODE_LEN=6    # количество знаков (цифр) в коде для входа, ипользуется в UserAuthCodeRepository

SECURITY_REG_CODE_TIMEOUT=300  # срок действия кода для регистрации (в секундах)
SECURITY_REG_CODE_DELAY=60 # раз в столько секунд можно запрашивать код для регистрации повторно
SECURITY_REG_CODE_LEN=6    # количество знаков (цифр) в коде для регистрации, ипользуется в UserRegCodeRepository
```

Создать файл конфигурации роутов config/routes/auth.yaml для проброски роутов из пакета в проект
```
auth_bundle:
  resource: '@AuthBundle/config/routes.yaml'
  prefix:  # нельзя добавлять префикс, либо придется его учитывать в securyty.firewall путях,
  name_prefix:  # нельзя добавлять префикс роутам
```
В config/packages/security.yaml поменять параметры авторизации, фаервол и 

```
security:    
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'    
    providers:        
        app_user_provider:
            entity:
                class: JustCommunication\AuthBundle\Entity\User
                property: phone
    firewalls:
        main:
            lazy: false
            provider: app_user_provider
            # аутентификатор из пакета 
            custom_authenticator: JustCommunication\AuthBundle\Security\Authenticator
            # для автоматичекого редиректа на страницу авторизации гостя при доступе к защищенным ресурсам
            form_login:
                login_path: app_login
            # для работы стандартной процедуры логаута
            logout:
                path: app_logout 
    access_control:
        # добавить эти разрешения доступа, для возможности авторизоваться/зарегистрироваться
        - { path: ^/user/login, roles: PUBLIC_ACCESS }
        - { path: ^/ajax/login, roles: PUBLIC_ACCESS }
        - { path: ^/ajax/auth, roles: PUBLIC_ACCESS }
        - { path: ^/ajax/reg, roles: PUBLIC_ACCESS }
        # какую-то часть проекта защитить правами доступа, например:
        - { path: ^/, roles: ROLE_USER }
```



### Подключение уведомлений
Создать в проекте подписчика на события на основе приведенного ниже кода.
App\EventSubscriber\UserNotifySubscriber.php

Смысл его работы в том, чтобы ловить UserNotifyEvent и отправлять сообщение пользователю посредством месенджеров на указанные контакты. Здесь реализован пример отправки кода авторизации/регистрации в телеграм, либо через сервис смс.
```
<?php
namespace App\EventSubscriber;

use JustCommunication\AuthBundle\Event\UserNotifyEvent;
use JustCommunication\FuncBundle\Service\FuncHelper;
use JustCommunication\SmsAeroBundle\Service\SmsAeroHelper;
use JustCommunication\TelegramBundle\Service\TelegramHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserNotifySubscriber implements EventSubscriberInterface
{
    public function __construct(private TelegramHelper $telegram, private SmsAeroHelper $smsAeroHelper, )
    {

    }

    public static function getSubscribedEvents()
    {
        return [
            UserNotifyEvent::class => 'sendNotification',
        ];
    }

    /**
     * $event->getUser() всегда будет иметь ->getPhone(), а вот со всем остальным не факт, в качестве юзера может присылаться заглушка
     * @param UserNotifyEvent $event
     * @return void
     */
    public function sendNotification(UserNotifyEvent $event)
    {

        // Планируется держать настройку вариантов отправки по email/sms/telegram у пользователя в профиле
        // Но пока суть такая, если есть возможность - отправляем на telegram, если нет или превышен некий лимит, то на смс
        //if ($_ENV['APP_ENV']=='dev')

        /*
        if (strpos($_ENV['APP_ENV'],'sms')!==false){

        }else{

        }
        */

        if (strpos($_ENV['APP_ENV'],'telegram')!==false){

        }else{

        }

        $ip = FuncHelper::getIP();

        $telegramUser = $this->telegram->findByPhone($event->getUser()->getPhone());
        $chat_id = $telegramUser?->getUserChatId();

        // Если запрос кода дважды был не услышан, то на третий раз делаем пометку, и отправляем это уведомление через sms, а не телегрм
        $resend_important_criteria = $_ENV['USER_NOTIFY_RESEND_IMPORTANT_COUNT']??3;
        $resend_important = $event->getNotificationCode()!=null && ($event->getNotificationCode()->getTries() % $resend_important_criteria) == 0;

        if (!$chat_id || $resend_important) {

            $smsMess = $event->getMessage();

            // 2023-02-08 Отправка дополнительной строчки для быстрого ввода смс по стандарту https://web.dev/web-otp/#format
            if ($event->getNotificationCode()!=null) {
                $url = parse_url($_ENV['APP_URL']);
                $smsMess .= "\r\n";
                $smsMess .= '@' . $url['host'] . ' #' . $event->getNotificationCode()->getCode();
                //$smsMess .= "\r\n" . '-= WebOTP formatted string =-';
            }
            // Даже если работает в режиме заглушки, всё равно фиксируем
            $sended = $this->smsAeroHelper->send($event->getUser()->getPhone(), $smsMess, 'auth', $event->getNotificationCode()?->getCode(), 0, 1, $ip);

            //---------------------------------------

            if (!$this->smsAeroHelper->isActive()){
                // На случай алярмы (неверно настроенных уведомлений) шлем весточку админу
                $this->telegram->sendMessage($this->telegram->getAdminChatId(),
                    '```' . "\r\n" . '[' . $_ENV['APP_NAME'] . '] SMS: ' . $event->getUser()->getPhone() . '```' .
                    "\r\n" .
                    'Товарищь админ, пользователь запросил уведомление, '.($chat_id?'телеграм есть но надо отправить именно смс':'a телеграма у него нет').', а смс у нас отключены, что делать?'.
                    "\r\n" .
                    ($resend_important?'Насильная отпрвка через смс из-за большого количества повторных запросов: '.$event->getNotificationCode()->getTries().' (критерий - каждые '.$resend_important_criteria.' раз(а))':'').
                    "\r\n" .
                    '```' . "\r\n" . $event->getMessage(). '```'
                );
            }else
            // Всё равно шлем уведомление админу, если положено
            if ($_ENV['USER_NOTIFY_ADMIN_COPY']) {
                $tel_res = $this->telegram->sendMessage($this->telegram->getAdminChatId(),
                    '```' . "\r\n" . '[' . $_ENV['APP_NAME'] . '] SMS: ' . $event->getUser()->getPhone() . '```' .
                    "\r\n" .
                    ($resend_important?'Насильная отпрвка через смс из-за большого количества повторных запросов: '.$event->getNotificationCode()->getTries().' (критерий - каждые '.$resend_important_criteria.' раз(а))':'').
                    "\r\n" .
                    $event->getMessage());
            }

        }else{
            $this->telegram->sendMessage($chat_id, $event->getMessage());
        }

        //$this->smsAeroHelper->

    }
}
```

Для работы вышеприведенного кода понадобится добавить в `.env` (`.env.local`) константы:
```
USER_NOTIFY="telegram" # smsaero/telegram пока не используется
USER_NOTIFY_ADMIN_COPY=1 # 1-отправлять в телегу копию, 0-нет
USER_NOTIFY_RESEND_IMPORTANT_COUNT=3 # на этой попытке отправлять коды насильно через смс а не телегу
```