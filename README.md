# neo4j-alexa-skills
Amazon Echo  Alexa Skills for querying Neo4j

[![Deploy](https://www.herokucdn.com/deploy/button.svg)](https://heroku.com/deploy)

### Complete tutorial

https://docs.google.com/document/d/1OdVme2kIhOXqbhHYSA2e_d07zjDs7M19sDuXmxJXKOM/edit

### Very quick start

[Link to quickstart tutorial](_resources/quickstart.md) 

### Run locally

```bash

# install php7 locally in one line :
curl -s https://php-osx.liip.ch/install.sh | bash -s 7.1

# install composer dependency manager
wget https://getcomposer.org/download/1.4.1/composer.phar
sudo mv composer.phar /usr/bin/composer

# install project dependencies
composer install

# run the stack
docker-compose up
```

### Skills

This is the Skill-APP URL to configure: `https://neo4j-alexa-skills.herokuapp.com/intent`

Skills: see also [skills.json](https://github.com/neo4j-contrib/neo4j-alexa-skills/blob/master/skill.json)

"Computer, ask Cypher ..."

* nodeCount: "Count {nodeLabel} nodes"
* findBetween: "Who is between {first} and {second} in {database}"
* neighbours: "What is {type} node {name} in {database}"

### Database Prep

For instance create an alexa:alexa user on sandbox databases and use them.

Also run this procedure call (on the labels + properties in your database).

```
call apoc.index.addAllNodes('search',{Person:['name'],Organization:['name']})
```

### Configuration

You configure databases to select from via `heroku config`

the `_name` suffix is used to select the database to search in.

```
heroku config:set NEO4J_URL_community=http://alexa:alexa@x.x.x.x:7474
heroku config:set NEO4J_URL_movies=http://alexa:alexa@x.x.x.x:33296
heroku config:set NEO4J_URL_panamapapers=http://alexa:alexa@x.x.x.x:33568
heroku config:set NEO4J_URL_trumpworld=http://alexa:alexa@x.x.x.x:32874
```
