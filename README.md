# Auth Bundle

Пакет должен был называться SecurityBundle, но произошел конфликт имен с Symfony/SecurityBundle.

Представляет из себя готовое решение по регистрации/авторизации через код подтверждения с телефона с формами для входа, таблицами хранения данных и прочего. 

## Установка 
`composer require justcommunication/auth-bundle`

На данный момент необходимо дописать в хост-composer.json:

```
"repositories": [
        {
            "type": "vcs",
            "url":  "git@github.com:mihaylo47/auth-bundle.git"
        },
        {
            "type": "vcs",
            "url":  "git@github.com:mihaylo47/func-bundle.git"
        }
    ],
```

## Требования
Для полноценной работы потребуется настроить конфигурацию хост проекта и подписчика на события для отправки сообщений.

## Подключение

В `.env` (`.env.local`) добавить константы:
```
APP_NAME="MY_PROJECT"
APP_URL="https://myproject.loc"
AJAX_DEBUG=0
```

```
SECURITY_LOGIN_ROUTE_REDIRECT=app_index # название роута на который произойдер редирект после успешной авторизации

SECURITY_AUTH_CODE_TIMEOUT=300 # срок действия кода для авторизации (в секундах)
SECURITY_AUTH_CODE_DELAY=10 # раз в столько секунд можно запрашивать код для регистрации повторно
SECURITY_AUTH_CODE_LEN=6    # количество знаков (цифр) в коде для входа, ипользуется в UserAuthCodeRepository

SECURITY_REG_CODE_TIMEOUT=300  # срок действия кода для регистрации (в секундах)
SECURITY_REG_CODE_DELAY=60 # раз в столько секунд можно запрашивать код для регистрации повторно
SECURITY_REG_CODE_LEN=6    # количество знаков (цифр) в коде для регистрации, ипользуется в UserRegCodeRepository

SECURITY_PASS_LEN_MIN=8
SECURITY_PASS_LEN_MAX=32


```

Здесь стоит обратить внимание на роут `app_index` - его следует заменить на свой существующий роут

Создать файл конфигурации роутов для проброски роутов из пакета в проект
```
# config/routes/auth.yaml 
auth_bundle:
  resource: '@AuthBundle/config/routes.yaml'
  prefix:  # нельзя добавлять префикс, либо придется его учитывать в securyty.firewall путях,
  name_prefix:  # нельзя добавлять префикс роутам
```
В security.yaml поменять параметры авторизации: провайдер (app_user_provider), фаервол (firewalls/main) и настроить ограничения доступа к контенту(access_control)

```
# config/packages/security.yaml
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
            # для автоматичекого редиректа на страницу авторизации гостя при попытке доступа к защищенным ресурсам
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

а еще в /public своего проекта необходимо скопировать содержимое public_content (js и css папки) бандла

### Подключение уведомлений
Создать в проекте подписчика на события на основе приведенного ниже кода.

Смысл его работы в том, чтобы ловить UserNotifyEvent и отправлять сообщение пользователю посредством месенджеров на указанные контакты. Здесь реализован пример отправки кода авторизации/регистрации в телеграм, либо через сервис смс.

Пример отправки уведомлений с помощью SmsAeroBundle и TelegramBundle можно посмотреть здесь [пример](./readme/event.md)

Ниже приведен пример подписчика который сохранит код в логи.
```
# App\EventSubscriber\UserNotifySubscriber.php
<?php
namespace App\EventSubscriber;

use JustCommunication\AuthBundle\Event\UserNotifyEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserNotifySubscriber implements EventSubscriberInterface
{

    public function __construct(private LoggerInterface $logger){
    }

    public static function getSubscribedEvents()
    {
        return [
            UserNotifyEvent::class => 'sendNotification',
        ];
    }

    public function sendNotification(UserNotifyEvent $event)
    {
        $this->logger->debug('[SMS TO: ' . $event->getUser()->getPhone() . ']: ' . $event->getMessage());
    }
}
```
