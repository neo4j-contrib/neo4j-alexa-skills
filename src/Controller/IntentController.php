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

            try {
                $neo4jClient->run('CREATE (n:Interaction) SET n = {values}', ['values' => ['intent' => $intent, 'slots' => json_encode($slots), 'time' => $ts]]);
            } catch (\Exception $e) {
                $application['monolog']->addWarning($e->getMessage());
            }
            switch ($intent) {
                case 'nodesCount':
                    return $this->nodesCountHandler($slots, $neo4jClient);
                case 'rawText':
                    return $this->rawTextHandler($slots, $neo4jClient);
                default:
                    return $this->returnAlexaResponse('Neo4j Alexa Skill Intent not found', self::TEXT_TYPE, "I'm unable to process this intent");
            }
        } catch (\Exception $e) {
            $application['monolog']->addWarning($e->getMessage());
            return new JsonResponse($e->getMessage(), 500);
        }
    }

    private function extractLabel(string $label)
    {
        # only use first word, remove trailing plural s  optionally check against labels in db ??
        if (preg_match("/^\s*(\w+)s\b\s*/",trim($label),$match)) {
            return ucfirst(strtolower(trim($match[1])));
        }
        return ucfirst(strtolower(trim($label)));
    }

    private function countNodes(string $label, Client $client,string $database)
    {
        $pattern = $label ? sprintf(':`%s`', $label) : "";
        $query = sprintf('MATCH (%s) RETURN count(*) AS c', $pattern);
        return $client->run($query,null,null,$database)->firstRecord()->get('c');
    }

    private function nodesCountHandler(array $slots, Client $client)
    {
        $response = sprintf('Expected a slot named %s', 'nodeLabel');
        $database = array_key_exists('nodeLabel', $slots) ? strtolower(" ","",$slots['database']): "default"
        if (array_key_exists('nodeLabel', $slots)) {
            $label = $this->extractLabel($slots['nodeLabel']);
            $result = $this->countNodes($label,$client,$database);
            if ($result > 0)
                $response = sprintf('There are %d %s nodes in the database', $result, $label);
            else
                $response = sprintf('There are %d total nodes in the database', $this->countNodes("",$client,$database));
        }

        return $this->returnAlexaResponse('Nodes Count', self::TEXT_TYPE, $response);
    }

    private function rawTextHandler(array $slots, Client $client)
    {
        if (!array_key_exists('Text', $slots)) {
            throw new \RuntimeException(sprintf('Expected a slot named %s', 'Text'));
        }

        $slot = $slots['Text'];
        $slot = strtolower($slot);
        $slot = str_replace('neo 4j', 'neo4j', $slot);

        $text = explode(' ', $slot);

        $query = ' 
        CREATE (i:RawText {time: timestamp()})
        WITH i
        UNWIND range(1, size({words})-1) AS e
        MERGE (w:Word {id: id(i) + toString(e-1) }) SET w.value = {words}[e-1]
        MERGE (w2:Word {id: id(i) + toString(e) }) SET w2.value = {words}[e]
        MERGE (w)-[:NEXT]->(w2)
        WITH i
        MATCH (w:Word {id: id(i) + toString(0)})
        MERGE (i)-[:FIRST_WORD]->(w)';

        $params = ['words' => $text];
        $client->run($query, $params);

        return $this->returnAlexaResponse('rawText', self::TEXT_TYPE, sprintf('Received the following text: "%s"', implode(' ', $text)));
    }

    public function getRawText(Request $request, Application $application)
    {
        /** @var Client $client */
        $client = $application['neo4j'];

        $content = json_decode($request->getContent(), true);

        $intent = $content['request']['intent']['name'];
        $slots = [];
        foreach ($content['request']['intent']['slots'] as $slot) {
            $slots[$slot['name']] = $slot['value'];
        }

        if (!array_key_exists('Text', $slots)) {
            throw new \RuntimeException(sprintf('Expected a slot named %s', 'Text'));
        }

        $text = explode(' ', $slots['Text']);

        $query = ' 
        CREATE (i:RawText {time: timestamp()})
        WITH i
        UNWIND range(1, size({words})-1) AS e
        MERGE (w:Word {id: id(i) + toString(e-1) }) SET w.value = {words}[e-1]
        MERGE (w2:Word {id: id(i) + toString(e) }) SET w2.value = {words}[e]
        MERGE (w)-[:NEXT]->(w2)
        WITH i
        MATCH (w:Word {id: id(i) + toString(0)})
        MERGE (i)-[:FIRST_WORD]->(w)';

        $params = ['words' => $text];
        $client->run($query, $params);

        return $this->returnAlexaResponse('rawText', self::TEXT_TYPE, sprintf('Received the following text: "%s"', implode(' ', $text)));
    }
}