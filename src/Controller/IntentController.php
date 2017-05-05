<?php

namespace Neo4j\Alexa\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use GraphAware\Neo4j\Client\Client;

class IntentController extends Controller
{
    public function handleIntent(Request $request, Application $application)
    {
        try {
            /** @var Client $neo4jClient */
            $neo4jClient = $application['neo4j'];

            $content = json_decode($request->getContent(), true);

            $intent = $content['request']['intent']['name'];
            $slots = [];
            foreach ($content['request']['intent']['slots'] as $slot) {
                $slots[$slot['name']] = $slot['value'];
            }
            $dt = new \DateTime("NOW", new \DateTimeZone("UTC"));
            $ts = $dt->format('Y-m-d-H:i:s');

            $neo4jClient->run('CREATE (n:Interaction) SET n = {values}', ['values' => ['intent' => $intent, 'slots' => json_encode($slots), 'time' => $ts]]);

            switch ($intent) {
                case 'nodesCount':
                    return $this->nodesCountHandler($slots, $neo4jClient);
                case 'rawText':
                    return $this->rawTextHandler($slots, $neo4jClient);
                default:
                    return $this->returnAlexaResponse('Neo4j Alexa Skill Intent not found', self::TEXT_TYPE, "I'm unable to process this intent");
            }
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), $e->getCode());
        }
    }

    private function nodesCountHandler(array $slots, Client $client)
    {
        if (!array_key_exists('nodeLabel', $slots)) {
            throw new \RuntimeException(sprintf('Expected a slot named %s', 'nodeLabel'));
        }

        $label = ucfirst(strtolower(trim($slots['nodeLabel'])));
        $query = sprintf('MATCH (n:`%s`) RETURN count(n) AS c', $label);
        $result = $client->run($query)->firstRecord()->get('c');

        return $this->returnAlexaResponse('Nodes Count', self::TEXT_TYPE, sprintf('There are %d %s nodes in the database', $result, $label));
    }

    private function rawTextHandler(array $slots, Client $client)
    {
        if (!array_key_exists('Text', $slots)) {
            throw new \RuntimeException(sprintf('Expected a slot named %s', 'Text'));
        }

        $text = explode(' ', $slots['Text']['value']);

        $query = 'MERGE (n:User {id: {user}}) 
        CREATE (i:Interaction {id: {sid} })
        MERGE (n)-[:SAID_TO_ALEXA]->(i)
        WITH i
        UNWIND range(1, size({words})-1) AS e
        MERGE (w:Word {id: {sid} + toString(e-1) }) SET w.value = {words}[e-1]
        MERGE (w2:Word {id: {sid} + toString(e) }) SET w2.value = {words}[e]
        MERGE (w)-[:NEXT]->(w2)
        WITH i
        MATCH (w:Word {id: {sid} + toString(0)})
        MERGE (i)-[:FIRST_WORD]->(w)';

        $params = ['words' => $text];
        $client->run($query, $params);

        return $this->returnAlexaResponse('rawText', self::TEXT_TYPE, sprintf('Received the following text: "%s"', $text));

    }

    public function getNodesCount(Request $request, Application $application)
    {
        /** @var Client $client */
        $client = $application['neo4j'];

        $content = json_decode($request->getContent(), true);

        $intent = $content['request']['intent']['name'];
        $slots = [];
        foreach ($content['request']['intent']['slots'] as $slot) {
            $slots[$slot['name']] = $slot['value'];
        }

        if (!array_key_exists('nodeLabel', $slots)) {
            throw new \RuntimeException(sprintf('Expected a slot named %s', 'nodeLabel'));
        }

        $label = ucfirst(strtolower(trim($slots['nodeLabel'])));
        $query = sprintf('MATCH (n:`%s`) RETURN count(n) AS c', $label);
        $result = $client->run($query)->firstRecord()->get('c');

        return $this->returnAlexaResponse('Nodes Count', self::TEXT_TYPE, sprintf('There are %d %s nodes in the database', $result, $label));
    }
}