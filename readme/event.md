# Пример отправки уведомлений через SmsAeroBundle и TelegramBundle

Здесь реализован пример отправки кода авторизации/регистрации в телеграм, либо через сервис смс.

```
# App\EventSubscriber\UserNotifySubscriber.php
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

        // Если запрос кода дважды был не услышан, то на третий раз делаем пометку, и отправляем это уведомление через sms, а не телеграм
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

Для работы вышеприведенного кода понадобится установить [SmsAeroBundle](https://github.com/mihaylo47/smsaero-bundle), [TelegramBundle](https://github.com/mihaylo47/telegram-bundle), а так же добавить в `.env` (`.env.local`) константы:
```
USER_NOTIFY="telegram" # smsaero/telegram пока не используется
USER_NOTIFY_ADMIN_COPY=1 # 1-отправлять в телегу копию, 0-нет
USER_NOTIFY_RESEND_IMPORTANT_COUNT=3 # на этой попытке отправлять коды насильно через смс а не телегу
```