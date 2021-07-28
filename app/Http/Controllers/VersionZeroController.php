<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Support\Str;

class VersionZeroController extends VersionOneController
{

    public function generateIlluminateResponse(): IlluminateResponse
    {
        $result = [
            'uid' => $this->egalRequest->getUuid(),
            'model' => $this->egalRequest->getModelName(),
            'action' => $this->egalRequest->getActionName(),
            'query' => $this->egalRequest->getParameters(),
        ];

        $resultMessage = $this->egalRequest->getResponse()->getActionResultMessage();
        $errorMessage = $this->egalRequest->getResponse()->getActionErrorMessage();

        if ($resultMessage) {
            $data = $resultMessage->getData();
            if ($this->getActionName() == 'actionGetItems' || $this->getActionName() == 'getItems') {
                foreach ($data as $key => $value) {
                    switch ($key) {
                        case 'items':
                            $data['relations'] = [];
                            foreach ($data[$key] as $itemKey => $itemValue) {
                                foreach ($this->egalRequest->getParameter('withs') as $with) {
                                    $withAsPascalCase = str_replace('_', '', ucwords(Str::singular($with), '_'));
                                    if (Str::singular($with) == $with && $data[$key][$itemKey][$with]) {
                                        $data['relations'][$withAsPascalCase][$data[$key][$itemKey][$with]['id']] = $data[$key][$itemKey][$with];
                                    } else {
                                        foreach ($data[$key][$itemKey][$with] as $relationKey => $relationValue) {
                                            $id = $data[$key][$itemKey][$with][$relationKey]['id'];
                                            $id = strlen((int)$id) == $id ? (int)$id : $id;
                                            $data['relations'][$withAsPascalCase][$id] = $relationValue;
                                        }
                                    }
                                    unset($data[$key][$itemKey][$with]);
                                }
                            }
                            break;
                        case 'total_count':
                            $data['count'] = $value;
                            break;
                        default:
                            unset($data[$key]);
                            break;
                    }
                }
            }
        } elseif ($errorMessage) {
            $data = $errorMessage->toArray();
        } else {
            $data = [
                'message' => $this->egalRequest->getResponse()->getErrorMessage()
            ];
        }

        $result['data'] = $data;

        return response(json_encode($result), $this->egalRequest->getResponse()->getStatusCode(), [
            'Content-Type' => 'application/json'
        ]);
    }

}
