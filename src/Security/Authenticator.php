<?php

namespace JustCommunication\AuthBundle\Security;

use JustCommunication\AuthBundle\Repository\UserAuthCodeRepository;
use JustCommunication\AuthBundle\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class Authenticator extends AbstractAuthenticator
{
    //private UserAuthCodeRepository $userAuthCodeRepository;
    //private UserRepository $userRepository;

    public function __construct(private UserAuthCodeRepository $userAuthCodeRepository, private UserRepository $userRepository, private UserPasswordHasherInterface $passwordHasher, private RouterInterface $router)
    {
        //$userAuthCodeRepository = $userAuthCodeRepository;
    }

    // отвечает на вопрос, надо ли запускать процедуру аутентификации
    public function supports(Request $request): ?bool
    {
        // TODO: Implement supports() method.
        return ($request->attributes->get('_route') === 'app_login' || $request->attributes->get('_route') === 'app_ajax_login')
            && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        // Паспорту нужно отдать такой логин который он найден сам в базе, поэтому форматируем
        $login = $this->userRepository->phoneFormat($request->request->get('login'));
        $pass = $request->request->get('password');
        $code = $request->request->get('code');

        $request->getSession()->set(
            Security::LAST_USERNAME,
            $login
        );
        $user = $this->userRepository->findByPhone($login);

        if ($user){
            if ($login!='' && $code!=''){
                // вход по коду
                $validCode = $this->userAuthCodeRepository->isValidCode($user->getId(), $code);
                if (!is_null($validCode)){
                    $this->userAuthCodeRepository->useCode($validCode);
                    // аутентифицируем, нужно вернуть объект Passport
                    //$passport = new Passport(new UserBadge($login), new PasswordCredentials($plaintextPassword));
                    //$passport = new SelfValidatingPassport(new UserBadge($login, function() use ($user, $request){
                    $passport = new SelfValidatingPassport(new UserBadge($login, function() use ($user){
                        return $user;
                    }));

                    return $passport;
                }else{
                    // пошел вон
                    throw new AuthenticationException('Код доступа указан не верно!');
                }

            }elseif ($login!='' && $pass!=''){
                // вход по паролю

                if ($this->passwordHasher->isPasswordValid($user, $pass)){
                    $passport = new SelfValidatingPassport(new UserBadge($login, function() use ($user){
                        return $user;
                    }));

                    return $passport;
                }else{
                    throw new AuthenticationException('Логин или пароль указаны не верно!');
                }
            }else{
                throw new AuthenticationException('Невозможно авторизоваться, нет данных');
            }
        }else{
            throw new AuthenticationException('Пользователь не найден');
        }

    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($request->attributes->get('_route') == 'app_ajax_login' || $request->attributes->get('_route') == 'app_ajax_reg') {
            return null; //ничего не возвращаем, не портим ответ, аякс сам разберется
        }else{
            return new RedirectResponse($this->router->generate($_ENV['SECURITY_LOGIN_ROUTE_REDIRECT']));
        }
        // TODO: Implement onAuthenticationSuccess() method.
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($request->attributes->get('_route') == 'app_ajax_login') {
            if ($request->hasSession()) {
                $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
            }
            //ничего не возвращаем, не портим ответ
        }
        // TODO: Implement onAuthenticationFailure() method.
        return null;
    }

//    public function start(Request $request, AuthenticationException $authException = null): Response
//    {
//        /*
//         * If you would like this class to control what happens when an anonymous user accesses a
//         * protected page (e.g. redirect to /login), uncomment this method and make this class
//         * implement Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface.
//         *
//         * For more details, see https://symfony.com/doc/current/security/experimental_authenticators.html#configuring-the-authentication-entry-point
//         */
//    }
}
