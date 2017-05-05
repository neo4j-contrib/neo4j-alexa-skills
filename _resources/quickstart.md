a) Create a new Alexa skill (en_GB) in your Amazon developer dashboard : https://developer.amazon.com

b) Paste the following interaction model :

```json
{
  "intents": [
    {
      "name": "AMAZON.CancelIntent",
      "samples": []
    },
    {
      "name": "AMAZON.HelpIntent",
      "samples": []
    },
    {
      "name": "AMAZON.StopIntent",
      "samples": []
    },
    {
      "name": "nodesCount",
      "samples": [
        "how many {nodeLabel} entities",
        "how many {nodeLabel}",
        "count {nodeLabel}",
        "how many {nodeLabel} in the database"
      ],
      "slots": [
        {
          "name": "nodeLabel",
          "type": "nodelabel",
          "samples": []
        }
      ]
    },
    {
      "name": "rawText",
      "samples": [
        "{Text}"
      ],
      "slots": [
        {
          "name": "Text",
          "type": "Text",
          "samples": []
        }
      ]
    }
  ],
  "types": [
    {
      "name": "nodelabel",
      "values": [
        {
          "name": {
            "value": "Person"
          }
        },
        {
          "name": {
            "value": "Movie"
          }
        },
        {
          "name": {
            "value": "Organisation"
          }
        }
      ]
    },
    {
      "name": "Text",
      "values": [
        {
          "name": {
            "value": "who is michael"
          }
        },
        {
          "name": {
            "value": "it is a sunny day"
          }
        },
        {
          "name": {
            "value": "shall we go for dinner"
          }
        },
        {
          "name": {
            "value": "how to do a neo4j backup"
          }
        },
        {
          "name": {
            "value": "how to load csv files in neo4j"
          }
        },
        {
          "name": {
            "value": "how to add a label dynamically in neo4j"
          }
        },
        {
          "name": {
            "value": "how to scale neo4j"
          }
        }
      ]
    }
  ]
}
```

c) Build the interaction model

d) Create a public Neo4j instance (for example on [GrapheneDB](https://graphenedb.com))

e) Deploy this application via the Heroku deploy button and fill in the neo4j url configuration variable

f) In the configuration of your Alexa Skill, mention the heroku app url on the `/intent` endpoint (`{heroku_url}/intent`)

g) Test your Alexa skill by saying, `Alexa, how many Movie `
