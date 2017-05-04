<?php

namespace Neo4j\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class RawTextSkillController
{
    public function rawTextIntent(Request $request, Application $application)
    {
        return $application['twig']->render('hello.twig', []);
    }
}