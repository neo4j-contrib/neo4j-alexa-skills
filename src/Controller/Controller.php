<?php

namespace Neo4j\Alexa\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

abstract class Controller
{
    const TEXT_TYPE = "PlainText";
    const SSML_TYPE = "SSML";

    protected function returnAlexaResponse($title, $type, $output)
    {
        $data = [
            'version' => '1.0',
            'response' => [
                'outputSpeech' => [
                    'type' => $type
                ],
                'card' => [
                    'content' => 'Neo4j Alexa Skill',
                    'title' => $title,
                    'type' => 'Simple'
                ],
                'shouldEndSession' => true,
            ],
            'sessionAttributes' => []
        ];

        if ($type === 'SSML') {
            $data['response']['outputSpeech']['ssml'] = $output;
        } else {
            $data['response']['outputSpeech']['text'] = $output;
        }

        return new JsonResponse($data);
    }
}