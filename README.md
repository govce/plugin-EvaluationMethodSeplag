# Avaliação SEPLAG

## Configuração do Plugin

1. Criar agente cultural da SEPLAG
2. Adicionar esse agente como avaliador no edital
3. Adicionar o código abaixo no config.php

```
'EvaluationMethodSeplag' => [
    'namespace' => 'EvaluationMethodSeplag',
    'config' => [
        'opportunity_id' => 0,
        'api_seplag' => [
            'auth' => [
                'method' => 'POST',
                'URL' => 'http://appsweb.seplag.ce.gov.br/spg-guardiao-backend/api/token/login',
                'keys' => [ // Dados de autenticação
                    'cpf' => '', 
                    'password' => '',
                    'idSistema' => 0
                ]
            ],
            'search' => [
                'method' => 'GET',
                'URL' => 'https://web5.seplag.ce.gov.br/eceara-backend/api/eceara/colaboradores/secult'
            ]
        ],
        'agent_id' => 0, // ID do agente responsável por avaliar
        'user_id' => 0 // ID do usuário dono do agente responsável por avaliar
    ]
]