<?php

declare(strict_types=1);

namespace Neo4j\Alexa\Controller;

use GraphAware\Neo4j\Client\Client;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardController
{
    public function index(Request $request, Application $application) : Response
    {
        return $application['twig']->render('hello.twig', []);
    }

    public function feed(Request $request, Application $application) : JsonResponse
    {
        /** @var Client $client */
        $client = $application['neo4j'];
        $interactions = [];

        $result = $client->run('MATCH (n:Interaction) RETURN n ORDER BY n.time DESC',null,'alexa');

        foreach ($result->records() as $record) {
            $i = $record->nodeValue('n');
            $interactions[] = [
                'id' => $i->identity(),
                'user' => sprintf('User %d', $i->identity()),
                'time' => $i->get('time'),
                'intent' => [
                    'name' => $i->value('intent', null),
                    'slots' => json_decode($i->get('slots'), true)
                ]
            ];
        }

        return new JsonResponse($interactions);
    }

    public function connections(Application $application) : JsonResponse
    {
        /** @var Client $client */
        $client = $application['neo4j'];
        $connections = [];
        foreach($_ENV as $k => $v) {
            if (preg_match("/^NEO4J_URL_?(.*)/",$k, $match)) {
                $alias = '' !== $match[1] ? $match[1] : 'default';
                $connection = $client->getConnectionManager()->getConnection($alias);
                try {
                    $client->run('RETURN 1', [], null, $connection->getAlias());
                    $connections[$alias] = true;
                } catch (\Exception $e) {
                    $connections[$alias] = false;
                }
            }
        }

        return new JsonResponse($connections);
    }
}