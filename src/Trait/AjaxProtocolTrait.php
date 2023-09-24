<?php


namespace JustCommunication\AuthBundle\Trait;

use JustCommunication\AuthBundle\Exception\AccessDeniedAjaxException;
use Symfony\Component\HttpFoundation\Response;


/**
 * Предоставляет возможности генерировать стандартизированный ajax ответ.
 * Trait для Controller
 */
trait AjaxProtocolTrait
{
    //use LoggerAware;

    protected $response;
    protected $ans;
    //protected $logger;


    //public function __construct(LoggerInterface $logger)
    public function init()
    {
        if (!$this->ans) {
            $this->response = new Response();
            $this->response->headers->set('Content-Type', 'application/json');
            // типовой ответ по ajax протоколу
            $this->ans = array(
                'result' => 'error',
                'code' => 'JCAB:000000X000',
                'message' => 'Answer not set',
                'trace_code' => array(),
                'data' => array(),
                'debug' => array()
            );
        }
        //$this->logger->debug(print_r($this->ans, true));
    }

    /**
     * Доопределяем ответ
     * @param $data
     *
     */
    public function setAns($data){
        $this->init();

        unset($data['trace_code']);
        // обновляем основной ответ
        //$this->logger->debug(print_r($this->ans, true));
        $this->ans = array_merge($this->ans, $data);

        return $this;
    }

    /**
     * Понадобится или нет -хз
     * @param $data
     */
    public function resetAns($data){
        $this->init();

        $this->ans = $data;
        return $this;
    }

    /**
     * Если наш ответ строится на основе предыдущего ответа (транзитивная прослойка)
     * код предыдущего уже должен быть в трейскоде
     * @param $data - полноценный стандартизированный ответ
     */
    public function preAns($data){
        $this->init();

        //var_dump($this->ans);
        //var_dump($data);
        //var_dump($data['trace_code']);
        //$this->ans['trace_code'] = array_merge(isset($data['trace_code'])?$data['trace_code']:array(),array(isset($data['code'])?$data['code']:'XXX'));
        $this->ans['trace_code'] = $data['trace_code'] ?? (isset($data['code']) ? array($data['code']) : array('XXX'));
        $this->ans['pre_ans'] = $data;
        return $this;
    }

    public function debugAns($data){
        $this->init();

        $this->ans['debug'] = $data;
        return $this;
    }

    public function jsonAns(){
        $this->init();

        // добавляем в трейс текущий код
        $this->ans['trace_code'] = array_merge(is_array($this->ans['trace_code'])?$this->ans['trace_code']:array($this->ans['trace_code']), array($this->ans['code']));

        // тут можно всякие локализации прикрутить

        // Встраиваем отладку в обычный ответ
        if ($_ENV["AJAX_DEBUG"]=='1') {
            $this->ans['message'].=' '.'[CODE: '.implode(' => ',$this->ans['trace_code']).']';

            if (isset($this->ans['debug']['message'])){
                $this->ans['message'].=' '.'[DEBUG: '.$this->ans['debug']['message'].']';
            }
            // тут можно что угодно придумать
        }

        return $this->json($this->ans);
    }

    /**
     * Проверяем доступ и бросаем специальное исключение по которому пойммем что стучались по ajax протоколу
     * @param $attribute
     * @param null $subject
     * @param string $message
     */
    public function denyAccessUnlessGrantedAjax($attribute, $subject = null, string $message = 'Access Denied.'): void
    {
        if (!$this->isGranted($attribute, $subject)) {
            $exception = new AccessDeniedAjaxException($message);
            $exception->setAttributes($attribute);
            $exception->setSubject($subject);
            throw $exception;
        }
    }


}