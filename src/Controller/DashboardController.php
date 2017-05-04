<?php

namespace Neo4j\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DashboardController
{
    public function feed(Request $request, Application $application)
    {
        $interactions = [
            [
                'id' => 'interaction-123-555-ff-dc',
                'user' => '123-fff-456',
                'intent' => [
                    'name' => 'rawText',
                    'slots' => []
                ]
            ]
        ];

        return new JsonResponse($interactions);
    }
}