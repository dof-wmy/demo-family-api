<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

use Symfony\Component\HttpKernel\Exception\HttpException;

use App\Jobs\DingtalkRobot;
class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    protected $dingtalkReport = [
        // [
        //     'exception' => HttpException::class,
        //     'robot'     => 'robot',
        // ],
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        // 异常告警处理
        foreach($this->dingtalkReport as $exceptionItem){
            if($exception instanceof $exceptionItem['exception']){
                $robot = array_get($exceptionItem, 'robot', 'robot');
                // TODO 不同异常对应不同的消息类型
                DingtalkRobot::dispatch([
                    'robot' => $robot,
                    "content" => (string) $exception,
                ])->onQueue('dingtalk');
            }
        }

        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        return parent::render($request, $exception);
    }
}
