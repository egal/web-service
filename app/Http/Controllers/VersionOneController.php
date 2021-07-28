<?php

namespace App\Http\Controllers;

use App\Action;
use App\Response;
use Egal\Core\Messages\ActionResultMessage;
use Egal\Model\Filter\FilterCombiner;
use Egal\Model\Filter\FilterCondition;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Support\Str;

class VersionOneController extends BaseController
{

    /**
     * @param string $routeLine
     * @param Request $request
     * @return IlluminateResponse
     * @throws Exception
     */
    public function __invoke(string $routeLine, Request $request): IlluminateResponse
    {
        $this->illuminateRequest = $request;
        $this->routeLine = $routeLine;
        $this->checkRouteLine();

        if ($this->getModelName() === 'Metadata') {
            switch ($this->getActionName()) {
                case 'getAll':
                    throw new Exception('Метод не реализован!');
                default:
                    throw new Exception('Метод не найден!');
            }
        }

        return parent::__invoke($routeLine, $request);
    }

    public function generateIlluminateResponse(): IlluminateResponse
    {
        $result = [
            'uid' => $this->egalRequest->getUuid(),
            'model' => $this->egalRequest->getModelName(),
            'action' => $this->egalRequest->getActionName(),
            'query' => $this->egalRequest->getParameters(),
        ];

        $response = $this->egalRequest->getResponse();

        $resultMessage = $response->getActionResultMessage();
        $errorMessage = $response->getActionErrorMessage();

        if ($resultMessage) {
            $data = $resultMessage->getData();
        } elseif ($errorMessage) {
            $data = $errorMessage->toArray();
        } else {
            $data = [
                'message' => $response->getErrorMessage()
            ];
        }

        $result['data'] = $data;

        return response(json_encode($result), $response->getStatusCode(), [
            'Content-Type' => 'application/json'
        ]);
    }

    public function getModelMetadata()
    {
        $data = $this->getDataFromService(
            $this->egalRequest->getServiceName(),
            $this->egalRequest->getModelName(),
            'getMetadata',
            [],
            $this->getToken()
        );
        return $data;
    }

    private function getActionCurrentName()
    {
        if (Str::contains($this->egalRequest->getActionName(), 'action')) {
            return $this->egalRequest->getActionName();
        } else {
            return 'action' . ucwords($this->egalRequest->getActionName());
        }
    }

    public function getParameters(): array
    {
        switch ($this->egalRequest->getActionName()) {
            case 'getItems':
            case 'actionGetItems':
                return $this->getParametersOfGetItemsAction();
            case 'create':
            case 'actionCreate':
                return $this->getParametersOfCreateAction();
            case 'update':
            case 'actionUpdate':
                return $this->getParametersOfUpdateAction();
            default:
                return $this->getParametersOfCustomAction();
        }
    }

    public function getParametersOfGetItemsAction(): array
    {
        $result = [];
        $query = $this->illuminateRequest->query();

        $result['per_page'] = isset($query['_count']) ? (int)$query['_count'] : 10;
        !isset($query['_from']) ?: $result['page'] = ceil((int)$query['_from'] / $result['per_page']);
        !isset($query['_with']) ?: $result['withs'] = array_map(function ($value) {
            return Str::snake($value);
        }, json_decode($query['_with'], true));

        if (isset($query['_order'])) {
            $_order = json_decode($query['_order'], true);
            foreach ($_order as $orderColumn => $orderDirection) {
                $result['order'][] = [
                    'column' => $orderColumn,
                    'direction' => $orderDirection
                ];
            }
        }

        $filter = [];

        $filterAddCombiner = function (&$filter, $combiner = FilterCombiner::AND) {
            if (count($filter) > 0) {
                $filter[] = $combiner;
            }
        };

        $filterAddCondition = function (&$filter, $column, $operator, $value, $combiner = FilterCombiner::AND) use ($filterAddCombiner) {
            $filterAddCombiner($filter, $combiner);
            $filter[] = [$column, $operator, $value];
        };

        $filterAddPart = function (&$filter, $part, $combiner = FilterCombiner::AND) use ($filterAddCombiner) {
            $filterAddCombiner($filter, $combiner);
            $filter[] = $part;
        };

        $whereQuery = array_filter($query, function ($value, $key) {
            return !Str::startsWith($key, '_');
        }, ARRAY_FILTER_USE_BOTH);

        foreach ($whereQuery as $column => $value) {
            $decodedValues = json_decode($value);
            if ($decodedValues && is_array($decodedValues)) {
                $filterPartInArray = [];
                foreach ($decodedValues as $decodedValue) {
                    $filterAddCondition(
                        $filterPartInArray, $column, FilterCondition::EQUAL_OPERATOR, $decodedValue, FilterCombiner::OR
                    );
                }
                $filterAddPart(
                    $filter, $filterPartInArray, FilterCombiner::AND
                );
            } elseif (!is_array($decodedValues)) {
                $filterAddCondition(
                    $filter, $column, FilterCondition::EQUAL_OPERATOR, $value, FilterCombiner::AND
                );
            }
        }


        if (count($filter) > 0
            && in_array($filter[array_key_last($filter)], [FilterCombiner::AND, FilterCombiner::OR])
        ) {
            unset($filter[array_key_last($filter)]);
        }

        if (isset($query['_range_from'])) {
            $decodedRangeFrom = json_decode($query['_range_from'], true);
            foreach ($decodedRangeFrom as $column => $value) {
                $filterAddCondition(
                    $filter, $column, FilterCondition::GREATER_OR_EQUAL_OPERATOR, $value, FilterCombiner::AND
                );
            }
        }

        if (isset($query['_range_to'])) {
            $decodedRangeFrom = json_decode($query['_range_to'], true);
            foreach ($decodedRangeFrom as $column => $value) {
                $filterAddCondition(
                    $filter, $column, FilterCondition::LESS_OR_EQUAL_OPERATOR, $value, FilterCombiner::AND
                );
            }
        }

        if (isset($query['_search'])) {
            $decodedSearch = json_decode($query['_search'], true);
            foreach ($decodedSearch as $column => $value) {
                $filterAddCondition($filter, $column, FilterCondition::CONTAIN_OPERATOR, $value, FilterCombiner::AND);
            }
        }

        $metadata = $this->getModelMetadata();

        if (isset($query['_full_search'])) {
            $fullSearchString = $query['_full_search'];
            $fullSearchFilterPart = [];
            foreach ($metadata['database_fields'] as $databaseField) {
                $filterAddCondition(
                    $fullSearchFilterPart,
                    $databaseField,
                    FilterCondition::CONTAIN_OPERATOR,
                    $fullSearchString,
                    FilterCombiner::OR
                );
            }
            $filterAddPart($filter, $fullSearchFilterPart, FilterCombiner::AND);
        }

        $result['filter'] = $filter;

        $actionCurrentName = $this->getActionCurrentName();
        $parameters = [];
        if (!isset($metadata['actions_metadata'][$actionCurrentName])) {
            throw new Exception('Такой action не существует!');
        }
        foreach ($metadata['actions_metadata'][$actionCurrentName]['parameters'] as $parameter) {
            if (isset($result[$parameter['name']])) {
                $parameters[$parameter['name']] = $result[$parameter['name']];
            }
        }

        return $parameters;
    }

    private function getParametersOfCreateAction()
    {
        $result = [];
        $result['attributes'] = $this->illuminateRequest->all();
        return $result;
    }

    private function getParametersOfUpdateAction()
    {
        $result = [];
        $result['attributes'] = $this->illuminateRequest->all();
        return $result;
    }

    private function getParametersOfCustomAction()
    {
        $result = [];

        $metadata = $this->getModelMetadata();
        $body = $this->illuminateRequest->all();
        $actionCurrentName = $this->getActionCurrentName();

        if (!isset($metadata['actions_metadata'][$actionCurrentName])) {
            throw new Exception('Такой action не существует!');
        }
        // todo: получше протестить
        if (isset($metadata['actions_metadata'][$actionCurrentName]['parameters'])) {
            foreach ($metadata['actions_metadata'][$actionCurrentName]['parameters'] as $key => $parameter) {
                if (!isset($body[$key])) continue;
                $result[$parameter['name']] = $body[$key];
            }
        }

        return $result;
    }

    /**
     * @return mixed
     * @throws \PhpAmqpLib\Exception\AMQPProtocolChannelException
     * @throws Exception
     */
    public function getMetadata()
    {
        $data = $this->getDataFromService(
            $this->egalRequest->getServiceName(),
            'ModelManager',
            'getAllModelsMetadata'
        );

        $endpoints = [];

        foreach ($data as $modelMetadata) {
            $actions = $modelMetadata['actions_metadata'];

            foreach ($actions as $actionName => $actionParams) {
                // todo: добавить проверку на роли и права пользователя
                $endpoints[$modelMetadata['model_short_name']][] = $actionName;
            }
        }

        return [
            'metadata' => $this->getOldMetadata(),
            'endpoints' => $endpoints,
            'menu' => $this->getMenuMetadata()
        ];
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getMenuMetadata()
    {
        $menuData = $this->getDataFromService(
            'interface',
            'MenuMetadata',
            'getItems',
            ['withs' => ['entriesTree']]
        );

        if (empty($menuData['items'])) {
            return [];
        }
        $menuItems = $menuData['items'][0]['entries_tree'];

        $data = array_map(function ($menuItem) {
                return [
                    'label' => $menuItem['name'],
                    'icon' => $menuItem['icon'],
                    'deep' => $this->getParentsTree($menuItem['parents_tree'])
                ];
        }, $menuItems);

        return $data;
    }

    private function getParentsTree(array $items)
    {
        $result = [];
        foreach ($items as $item) {
            $result[] = [
                'label' => $item['name'],
                'route' => $item['id']
            ];
        }
        return $result;
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
        return $this->getExplodedRouteLine()[2] ?? 'getItems';
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
        if (count($explodedRouteLine) > 4 || count($explodedRouteLine) < 2) {
            throw new Exception('Route line is incorrect!');
        }
    }

    /**
     * @param string $serviceName
     * @param string $modelName
     * @param string $actionName
     * @param array $params
     * @return mixed
     * @throws \PhpAmqpLib\Exception\AMQPProtocolChannelException
     */
    private function getDataFromService(string $serviceName, string $modelName, string $actionName, $params = [], $token = null)
    {
        $action = new Action($serviceName, $modelName, $actionName, $params, $token);
        $action->openConnection();
        $action->publish();
        $action->waitReplyMessages();
        $action->closeConnection();
        $result = $action->response->getActionResultMessage();

        if (!$result) {
            $message = 'Ошибка при получении данных!';
            $actionErrorMessage = $action->response->getActionErrorMessage();
            if ($actionErrorMessage) {
                $message .= ' (' . $actionErrorMessage->getMessage() . ')';
            }
            throw new Exception($message, $actionErrorMessage ? $actionErrorMessage->getCode() : 500);
        }

        return $result->getData();
    }

    private function getOldMetadata()
    {
        $data = $this->getDataFromService(
            'interface',
            'InterfaceMetadata',
            'getItem',
            [
                'id' => $this->egalRequest->getServiceName() . '-metadata'
            ]
        );

        return isset($data['data']) ? json_decode($data['data']) : [];
    }
}
