<?php

namespace App\Http\Controllers;

use Egal\Core\Communication\Request as EgalRequest;
use Exception;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Support\Str;
use Laravel\Lumen\Routing\Controller as LaravelBaseController;

/**
 * Class BaseController
 * @package App\Http\Controllers
 */
abstract class BaseController extends LaravelBaseController
{

    protected EgalRequest $egalRequest;
    protected string $routeLine;
    protected IlluminateRequest $illuminateRequest;

    /**
     * @param string $routeLine
     * @param IlluminateRequest $request
     * @return IlluminateResponse
     * @throws Exception
     */
    public function __invoke(string $routeLine, IlluminateRequest $request): IlluminateResponse
    {
        $this->illuminateRequest = $request;
        $this->routeLine = $routeLine;
        $this->checkRouteLine();

        $this->egalRequest = new EgalRequest(
            $this->getToServiceName(),
            $this->getModelName(),
            $this->getActionName()
        );

        $this->egalRequest->disableServiceAuthorization();
        $this->egalRequest->setToken($this->getToken());
        $this->egalRequest->addParameters($this->getParameters());
        $someId = $this->getSomeIdOrNull();
        is_null($someId) ?: $this->egalRequest->addParameter('id', $someId);
        $this->egalRequest->call();

        // TODO: temporary solution @see https://github.com/egal/egal-framework-php-package/issues/62
        $response = $this->egalRequest->response;
        if ($response->getErrorMessage() === 'Service not responding!') {
            $this->egalRequest->call();
        }

        return $this->generateIlluminateResponse();
    }

    abstract public function generateIlluminateResponse(): IlluminateResponse;

    /**
     * @return string
     */
    public function getRouteLine(): string
    {
        return $this->routeLine;
    }

    /**
     * Проверяется строка запроса на следующие правила:
     * строка запроса содержит максимум 4 минимум 2 элемента разделенные символом "/";
     *
     * @throws Exception
     */
    public function checkRouteLine()
    {
        $explodedRouteLine = $this->getExplodedRouteLine();
        if (count($explodedRouteLine) > 4 || count($explodedRouteLine) < 3) {
            throw new Exception('Route line is incorrect!');
        }
    }

    /**
     * Возвращается массив строки запроса
     * сформированный разделением через сепаратор символа "/"
     *
     * @return array
     */
    public function getExplodedRouteLine(): array
    {
        return explode('/', $this->routeLine);
    }

    abstract public function getParameters(): array;

    /**
     * Возвращается токен
     * указанный в Authorization header
     * без префикса Bearer
     *
     * @return string
     */
    public function getToken(): ?string
    {
        return Str::replaceFirst('Bearer ', '', $this->illuminateRequest->header('Authorization'));
    }

    /**
     * Возвращает action
     * Если указан в адресной строке - вернет указанный
     * Если не указан в строке - вернет в зависимости от HTTP метода
     *
     * host:port/service/Model/action/id?params...
     *                         ^^^^^
     *
     * @return string
     * @throws Exception
     */
    public function getActionName(): string
    {
        return $this->getExplodedRouteLine()[2];
    }

    /**
     * Возвращает id указанный в адресной строке
     * либо null
     *
     * host:port/service/Model/action/id?params...
     *                                ^^
     *
     * @return int|string|null
     */
    public function getSomeIdOrNull()
    {
        $explodedRouteLine = $this->getExplodedRouteLine();
        switch (count($explodedRouteLine)) {
            case 4:
                $someId = $explodedRouteLine[3];
                return strlen((int)$someId) == strlen($someId) ? (int)$someId : $someId;
            default:
                return null;
        }
    }

    /**
     * Возвращает название сервиса, к которому идет обращение,
     * которое указано в строке запроса
     *
     * host:port/service/Model/action/id?params...
     *           ^^^^^^
     *
     * Если не находится название сервиса - срабатывает исключение
     *
     * @return string
     * @throws Exception
     */
    public function getToServiceName(): string
    {
        $explodedRouteLine = $this->getExplodedRouteLine();
        if (array_key_exists(0, $explodedRouteLine)) {
            return $explodedRouteLine[0];
        } else {
            throw new Exception('Service not specified!');
        }
    }

    /**
     * Возвращает название модели,
     * которое указано в строке запроса
     *
     * host:port/service/Model/action/id?params...
     *                   ^^^^
     *
     * Если не находится название модели - срабатывает исключение
     *
     * @return string
     * @throws Exception
     */
    public function getModelName(): string
    {
        return $this->getExplodedRouteLine()[1];
    }

}
