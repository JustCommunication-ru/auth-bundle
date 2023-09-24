<?php

namespace JustCommunication\AuthBundle\Controller;

use JustCommunication\AuthBundle\Entity\User;
use JustCommunication\AuthBundle\Event\UserNotifyEvent;
use JustCommunication\AuthBundle\Repository\UserAuthCodeRepository;
use JustCommunication\AuthBundle\Repository\UserRegCodeRepository;
use JustCommunication\AuthBundle\Repository\UserRepository;
use JustCommunication\AuthBundle\Security\Authenticator;
use JustCommunication\AuthBundle\Trait\AjaxProtocolTrait;

use Exception;
use JustCommunication\FuncBundle\Service\FuncHelper;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SecurityController extends AbstractController
{
    use AjaxProtocolTrait;
    private $curl;

    public function __construct(HttpClientInterface $client)
    {
        $this->curl = $client;
        //$this->csrfCookie = $csrfCookie;

    }

    /**
     * Форма входа пользователя
     * Поддерживает обработку пост параметров для входов
     * осоновной роут app_login куда симфони будет редиректить пользователей в случае чего
     *
     * @param AuthenticationUtils $authenticationUtils
     * @param $redirect_to_name - route name for redirect
     * @return Response
     */
    #[Route('/user/login/{redirect_to_name}', name: 'app_login', priority: "100")]
    public function login(AuthenticationUtils $authenticationUtils, Security $security, $redirect_to_name=''): Response
    {
        // При посещении страницы в авторизованном режиме при необходимсти пинаем пользователя.
        if ($security->getUser() && $_ENV['SECURITY_LOGIN_DEFAULT_REDIRECT_ROUTE']!='') {
            return $this->redirectToRoute($_ENV['SECURITY_LOGIN_DEFAULT_REDIRECT_ROUTE']);
        }
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        // Честно пытаемся установить перенабравление: 1) куда просили в ссылке 2) куда установлен дефолт 3) на главную
        if ($redirect_to_name!='') {
            try {
                $redirect_to_path = $this->generateUrl($redirect_to_name);
            } catch (RouteNotFoundException $e) {
                $redirect_to_path = '/';
            }
        }elseif($_ENV['SECURITY_LOGIN_DEFAULT_REDIRECT_ROUTE']) {
            try {
                $redirect_to_path = $this->generateUrl($_ENV['SECURITY_LOGIN_DEFAULT_REDIRECT_ROUTE']);
            } catch (RouteNotFoundException $e) {
                $redirect_to_path = '/';
            }
        }else{
            $redirect_to_path = '/';
        }

        return $this->render('@Auth/security/login.html.twig', [
                'last_username' => $lastUsername,
                'error' => $error?$error->getMessage():'',
                'redirect_to'=>$redirect_to_path,
                'page_title' =>'Вход в систему '.$_ENV['APP_NAME'],
                'app_year'=>date('Y'),
                'security_auth_code_delay'=>$_ENV['SECURITY_AUTH_CODE_DELAY'],
                'security_reg_code_delay'=>$_ENV['SECURITY_REG_CODE_DELAY'],
                'security_pass_len_min'=>$_ENV['SECURITY_PASS_LEN_MIN'],
                'security_pass_len_max'=>$_ENV['SECURITY_PASS_LEN_MAX'],

            ]
        );
    }

    /**
     * Не используется?
     * Страничка с информацией, что у пользователя нет прав доступа к инструменту
     */
    #[Route('/user/deny/{redirect_to_name}', name: 'app_deny', priority: "100")]
    public function deny(AuthenticationUtils $authenticationUtils, $redirect_to_name=''): Response
    {
        try {
            $redirect_to_path = $this->generateUrl($redirect_to_name);
        } catch (RouteNotFoundException $e){
            $redirect_to_path = '/';
        }

        return $this->render('@Auth/security/deny.html.twig', ['redirect_to'=>$redirect_to_path]);
    }


    /**
     * DEPRECATED Сейчас всё через форму логина, регистрация там же
     * Форма регистрации
     * @param AuthenticationUtils $authenticationUtils
     * @return Response
     */
    #[Route('/user/reg', name: 'app_reg', priority: "100")]
    public function registration(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->redirectToRoute('app_login');
        //return $this->render('@Auth/security/reg.html.twig', ['error' => '']);
    }

    /**
     * Фиктивный экшн ради роута для работы стандартной симфонийской системы логаута
     * @return mixed
     */
    #[Route('/logout', name: 'app_logout', priority: "100")]
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
        //return $this->redirectToRoute('app_login');
    }

    /**
     * Дублирующий роут
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    #[Route('/user/logout', name: 'app_user_logout', priority: "100")]
    public function app_user_logout()
    {
        return $this->redirectToRoute('app_logout');
    }

    /**
     * Специальная точка входа пост данных для авторизации по ajax
     * @param AuthenticationUtils $authenticationUtils
     * @param Security $security
     * @return JsonResponse
     */
    #[Route('/ajax/login', name: 'app_ajax_login', priority: "100")]
    public function ajax_login(AuthenticationUtils $authenticationUtils, Security $security/*, PublicQueryResolver $pqr*/): JsonResponse
    {

        $cookie = null;
        if ($security->getUser()) {
            $user = $security->getUser();
            // Здесь должен быть такой же массив как PublicQueryResolver::principal()
            // записать в ответ параметры успешного завершения функции
            $this->setAns(array('result'=>'success', 'code'=>'JCAB:20230629CS001', 'message'=>'', 'data'=>(array)$user));
            // создать куку с новым csrf токеном
           //$cookie = $this->csrfCookie->refreshToken();

        }else{
            // чтобы это заработало надо в authenticator в сессию передать название(!) эксепшена
            $error = $authenticationUtils->getLastAuthenticationError();
            $lastUsername = $authenticationUtils->getLastUsername();
            $this->setAns(array('result'=>'error', 'code'=>'JCAB:20230629CS002', 'message'=>$error?$error->getMessage():''));
        }
        // сформировать json ответ и отдать его клиенту
        $jsonResponse = $this->jsonAns();

        // записать созданную куку в ответ, если она создалась
        if($cookie){
            $jsonResponse->headers->setCookie($cookie);
            //$jsonResponse->headers->setCookie(new Cookie('sometoken', 'token_value', time() + 600, '', null, false, false));

        }
        return $jsonResponse;
    }




    /**
     * Запрос кода для входа (подтверждение номера телефона)
     * @param Security $security
     * @param Request $request
     * @param UserRepository $userRepository
     * @param UserAuthCodeRepository $userAuthCodeRepository
     * @return JsonResponse
     */
    #[Route('/ajax/auth_get_code', name: 'app_ajax_auth_get_code', priority: "10000")]
    public function ajax_auth_get_code(Security $security,Request $request, UserRepository $userRepository, UserAuthCodeRepository $userAuthCodeRepository, EventDispatcherInterface $eventDispatcher): JsonResponse
    {
        if ($security->getUser()) {
            $this->debugAns(array('message'=>$security->getUser()->getUsername()))
                ->setAns(array('result'=>'error', 'code'=>'JCAB:20230629CS003', 'message'=>'Вы уже авторизованы.'));
        }else{
            $login = $request->request->get('login');

            if (FuncHelper::isPhone($login)) {
                $user = $userRepository->findByPhone($login);
                if ($user) {
                    $existCode = $userAuthCodeRepository->getActiveCodeByPhone($user->getPhone());
                    if (!$existCode || $existCode->getRemainTimeForRepeat()==0) {
                        if ($existCode) {
                            $newCode = $userAuthCodeRepository->prolongCode($existCode);
                        } else {
                            $newCode = $userAuthCodeRepository->newCode($user);
                        }

                        //$mess = 'Код для авторизации: *'.$code.'*'.($chat_id?'':'. Получать код в телеграм https://t.me/'.$telegram->config['bot_name']);
                        $mess = 'Код для авторизации: *' . $newCode->getCode() . '*';
                        $event = new UserNotifyEvent($user, $mess, $newCode);
                        $eventDispatcher->dispatch($event, UserNotifyEvent::class);

                        $this->setAns(array('result' => 'success', 'code' => 'JCAB:20230629CS004', 'message' => "Код авторизации успешно отправлен", 'data'=>['sec'=>$newCode->getRemainTimeForRepeat()]));
                    }else{
                        //$userAuthCodeRepository->idleTry($existCode); а зачем? ну попросил, мы не дали
                        $this->setAns(array('result' => 'success', 'code' => 'JCAB:20230630CS013', 'message' => "Код авторизации уже был отправлен ранее", 'data'=>['sec'=>$existCode->getRemainTimeForRepeat()]));
                    }
                } else {
                    $this->setAns(array('result' => 'error', 'code' => 'JCAB:20230629CS005', 'message' => "Пользователь не найден."));
                }
            }else{
                $this->setAns(array('result' => 'error', 'code' => 'JCAB:20230629CS006', 'message' => "Телефон указан не корректно"));
            }
        }
        return $this->jsonAns();

    }

    /**
     * Запрос кода для регистрации (подтверждение номера телефона)
     * @param Security $security
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/ajax/reg_get_code', name: 'app_ajax_reg_get_code', priority: "10000")]
    public function ajax_reg_get_code(Security $security, Request $request, UserRepository $userRepository, UserRegCodeRepository $userRegCodeRepository, EventDispatcherInterface $eventDispatcher): JsonResponse
    {
        if ($security->getUser()) {
            $this->debugAns(array('message'=>$security->getUser()->getUsername()))->setAns(array('result'=>'error', 'code'=>'JCAB:20230629CS007', 'message'=>'Вы уже авторизованы.'));
        }else{
            $phone = $request->request->get('phone');
            $email = $request->request->get('email');
            $name = $request->request->get('name');

            if (FuncHelper::isPhone($phone)) {
                $user_by_phone = $userRepository->findByPhone($phone);
                if (!$user_by_phone) {
                    // Не забываем, что телефон должен быть в определенном формате
                    $phone = $userRepository->phoneFormat($phone);

                    if (FuncHelper::isEmail($email)) {
                        $user_by_email = $userRepository->findOneBy(['email' => $email]);
                        if (!$user_by_email){
                            // Отлично, идентификаторы свободны, можно регать.
                            $existCode = $userRegCodeRepository->getActiveCodeByPhone($phone);

                            $payload = ['name'=>$name, 'email'=>$email];

                            if (!$existCode || $existCode->getRemainTimeForRepeat()==0) {

                                if ($existCode) {
                                    // Тут можно проверку делать на то, что изменилось имя/мыло
                                    // Можно варнинг делать, но мы просто перезаписывать будем
                                    // Уже пробовал недавно регаться, используем тот же код
                                    $newCode = $userRegCodeRepository->prolongCode($existCode, $payload);
                                } else {
                                    $newCode = $userRegCodeRepository->newCode($phone, FuncHelper::getIP(), $payload);
                                }

                                $user = new User(); // залепный юзер для отправки уведомления
                                $user->setPhone($phone)->setId(0)->setName($name)->setEmail($email);

                                $mess = 'Код подтверждения номера телефона:: *' . $newCode->getCode() . '*';
                                $event = new UserNotifyEvent($user, $mess, $newCode);
                                $eventDispatcher->dispatch($event, UserNotifyEvent::class);

                                $this->setAns(array('result' => 'success', 'code' => 'JCAB:20230629CS012', 'message' => "Код подтверждения успешно отправлен", 'data'=>['sec'=>$newCode->getRemainTimeForRepeat()]));
                            }else{
                                $this->setAns(array('result' => 'success', 'code' => 'JCAB:20230630CS014', 'message' => "Код подтверждения уже был отправлен ранее", 'data'=>['sec'=>$existCode->getRemainTimeForRepeat()]));
                            }
                        }else{
                            $this->setAns(array('result' => 'error', 'code' => 'JCAB:20230629CS011', 'message' => "Пользователь с таким email уже зарегистрирован"));
                        }
                    }else{
                        $this->setAns(array('result' => 'error', 'code' => 'JCAB:20230629CS010', 'message' => "Email указан неверно"));
                    }
                }else{
                    $this->setAns(array('result' => 'error', 'code' => 'JCAB:20230629CS009', 'message' => "Пользователь с таким номером телефона уже зарегистрирован"));
                }
            }else{
                $this->setAns(array('result' => 'error', 'code' => 'JCAB:20230629CS008', 'message' => "Телефон указан не корректно"));
            }

        }
        return $this->jsonAns();

    }

    /**
     * Несмотря на то, что в payload хранятся email и name прошедшие проверку на уникальнось, здесь придется делать всё заново
     * @param AuthenticationUtils $authenticationUtils
     * @param Security $security
     * @return JsonResponse
     */
    #[Route('/ajax/reg', name: 'app_ajax_reg', priority: "10000")]
    public function ajax_reg(Security $security, Request $request, UserRepository $userRepository, UserRegCodeRepository $userRegCodeRepository, EventDispatcherInterface $eventDispatcher): JsonResponse
    {
        if ($security->getUser()) {
            $this->debugAns(array('message'=>$security->getUser()->getUsername()))->setAns(array('result'=>'error', 'code'=>'JCAB:20230630CS015', 'message'=>'Вы уже авторизованы.'));
        }else{
            $phone = $request->request->get('phone');
            $code = $request->request->get('code');
            $pass = $request->request->get('pass');

            if ($phone && FuncHelper::isPhone($phone)) {



                $is_valid_pass = $pass && FuncHelper::isPass($pass, $_ENV['SECURITY_PASS_LEN_MIN'], $_ENV['SECURITY_PASS_LEN_MAX']);
                if ($is_valid_pass) {
                    $user_by_phone = $userRepository->findByPhone($phone);
                    if (!$user_by_phone) {
                        // Не забываем, что телефон должен быть в определенном формате
                        $phone = $userRepository->phoneFormat($phone);

                        $existCode = $userRegCodeRepository->isValidCode($phone, $code??'');

                        if ($existCode) {
                            // Если код действителен, проверяем еще раз email
                            $email = $existCode->getPayload()['email'] ?? '';
                            $name = $existCode->getPayload()['name'] ?? '';
                            $user_by_email = $userRepository->findOneBy(['email' => $email]);
                            if (!$user_by_email) {

                                $userRegCodeRepository->useCode($existCode);
                                $user = $userRepository->newUser($phone, $email, $name, $pass); // РЕГИСТРАЦИЯ НАСТОЯЩГО ПОЛЬЗОВАТЕЛЯ

                                // тут можно уведомление на email

                                // Логинимся автоматом
                                $security->login($user, Authenticator::class);


                                $this->setAns(array('result' => 'success', 'code' => 'JCAB:20230630CS016', 'message' => "Код авторизации успешно отправлен"));
                            } else {
                                $this->setAns(array('result' => 'error', 'code' => 'JCAB:20230630CS017', 'message' => "Пользователь с таким email уже был зарегистрирован"));
                            }
                        } else {
                            $this->setAns(array('result' => 'error', 'code' => 'JCAB:20230630CS018', 'message' => "Неверный код регистрации"));
                        }
                    } else {
                        $this->setAns(array('result' => 'error', 'code' => 'JCAB:20230630CS019', 'message' => "Пользователь с таким номером телефона уже зарегистрирован"));
                    }
                }else{
                    $this->setAns(array('result' => 'error', 'code' => 'JCAB:20230630CS020', 'message' => "Пароль указан не корректно"));
                }
            }else{
                $this->setAns(array('result' => 'error', 'code' => '20230630CS021', 'message' => "Телефон указан не корректно"));
            }

        }
        return $this->jsonAns();
    }

}
