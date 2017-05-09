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
                $neo4jClient->run(
                'CREATE (n:Interaction) SET n = {values}', 
                ['values' => ['intent' => $intent, 'slots' => json_encode($slots), 'time' => $ts]],
                null,"alexa");
            } catch (\Exception $e) {
                $application['monolog']->addWarning($e->getMessage());
            }
            switch ($intent) {
# list registered databases
# database statistics (top-3 labels, top 3 rel-types)
                case 'nodesCount':
                    return $this->nodesCountHandler($slots, $neo4jClient);
                case 'findBetween':
                    return $this->findBetweenHandler($slots, $neo4jClient);
                case 'neighbours':
                    return $this->neighboursHandler($slots, $neo4jClient, $application['monolog']);
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
        $database = array_key_exists('database', $slots) ? strtolower(str_replace(" ","",$slots['database'])): "default";
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

    private function findBetweenHandler(array $slots, Client $client)
    {
        $response = 'Missing inputs. I need two entities to connect.';
        $database = array_key_exists('database', $slots) ? strtolower(str_replace(" ","",$slots['database'])): "default";
        if (array_key_exists('first', $slots) && array_key_exists('second', $slots)) {
            $result = $client->run(

            "CALL apoc.index.search('search',{first}+'~')  yield node as from " .
            "CALL apoc.index.search('search',{second}+'~') yield node as to WITH from, to LIMIT 1 ".
            "OPTIONAL MATCH path = shortestPath((from)-[*..10]-(to)) ".
            "WITH *, [x IN nodes(path) | coalesce(x.name, x.title, x.description, id(x))] as names ".
            "RETURN coalesce(from.name, from.title, {first}) as first, " .
            " coalesce(to.name, to.title, {second}) as second, names[1..-1] as names",

             ["first"=>$slots['first'],"second"=>$slots['second']],null,$database)->firstRecord();

             $response = sprintf('Between %s and %s there are %s', 
                $result->get("first") ?: $slots['first'], 
                $result->get("second") ?: $slots['second'], 
                implode(', ', $result->get("names")));
        }

        return $this->returnAlexaResponse('Path Between', self::TEXT_TYPE, $response);
    }

    # "What are the top {2} neighbours {connected to} node {Mar a Lago} in {trumpworld}"
    # "What/who is {connected to} node {jared kushner} in {trumpworld}"
    # "Who {acted in} node {the matrix} in {movies}"
    # "Who {directed} node {the matrix} in {movies}"
    # "What {acted in} node {clint eastwood} in {movies}"
    private function neighboursHandler(array $slots, Client $client, $log)
    {
        $response = 'Missing inputs. I need the entitiy to inspect.';
        $database = array_key_exists('database', $slots) ? strtolower(str_replace(" ","",$slots['database'])): "default";
        if (array_key_exists('name', $slots)) {
        	$type = array_key_exists('type', $slots) ? (":`" . strtoupper(str_replace(" ","_",$slots['type']))) ."`" : "";
            $query = "CALL apoc.index.search('search',{name}+'~') yield node as from " .
            "RETURN coalesce(from.name, from.title, {name}) as from, size((from)-[".$type."]-()) as count, " .
            "[(from)-[".$type."]-(to) | coalesce(to.name, to.title, to.description, id(to))][0..{limit}] as neighbours";
            $log->addWarning($query."; name:".$slots['name']." ".$database);
            $result = $client->run($query,
             ["name"=>$slots['name'],"limit"=>(intval($slots['limit'] ?: 5))],null,$database)->firstRecord();

             $response = sprintf('%d nodes have or are %s to/of %s, for example: %s', 
                $result->get("count"),
                $slots['type'], $result->get("name") ?: $slots['name'],
                implode(', ', $result->get("neighbours")));
            $log->addWarning($response);
        }

        return $this->returnAlexaResponse('Neighbours of', self::TEXT_TYPE, $response);
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
        $client->run($query, $params,null,"alexa");

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
        $client->run($query, $params,null,"alexa");

        return $this->returnAlexaResponse('rawText', self::TEXT_TYPE, sprintf('Received the following text: "%s"', implode(' ', $text)));
    }
}