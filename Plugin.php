<?php 

namespace EvaluationMethodSeplag;

require __DIR__ . './../vendor/autoload.php';

use MapasCulturais\App;
use MapasCulturais\i;
use GuzzleHttp\Client;
use MapasCulturais\Entities\RegistrationEvaluation;

class Plugin extends \MapasCulturais\Plugin {
  public function __construct(array $config = [])
  {
      parent::__construct($config);
  }

  public function _init() {
    $app = App::i();

    $plugin = $this;
    
    $app->hook('template(opportunity.single.header-inscritos):end', function () use($plugin, $app) {
      $opportunity = $this->controller->requestedEntity;
      
      if ($opportunity->id == $plugin->config['opportunity_id']) {
        $this->part('seplag/opportunity-button-seplag', [ 'opportunity' => $opportunity ]);
      }
    });
  }

  function cron() {
    set_time_limit(-1);

    $this->config = isset($this->config["cron_limit"]) ? $this->config["cron_limit"] : 50;
    
    $app = App::i();
    $now = date('d/mY H:i:s');
    
    $this->auth();
        
    if (!isset($this->token)) {
      // Isso está feio, arrumar depois.
      echo 'Erro ao se autenticar com a SEPLAG!. Informe aos desenvolvedores.';
      return;
    }

    $user = $app->repo("User")->find($this->config["user_id"]);
    $opportunity = $app->repo("Opportunity")->find($this->config["opportunity_id"]);

    $sql = "
      SELECT
        reg.id, 
        REPLACE(REPLACE(REPLACE(REPLACE(reg_me.value, '.', '' ),'/',''),'-',''), '\"', '') AS value,
        reg_ev.id AS exists
      FROM 
        registration reg 
        JOIN registration_meta reg_me ON reg_me.object_id = reg.id AND reg_me.key = 'field_26519'
        LEFT JOIN registration_evaluation reg_ev ON reg_ev.registration_id = reg.id
      WHERE
        reg.status = 1
        AND reg.opportunity_id = {$this->config['opportunity_id']}
        AND reg_ev.id IS NULL
      ORDER BY reg.sent_timestamp DESC
      LIMIT {$this->config['cron_limit']};
    ";

    $stmt = $app->em->getConnection()->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetchAll();

    foreach($data as $d) {
      $response = null;
  
      try {
        $response = $this->search($d["value"]);
      } catch (\Exception $e) {
        $app->log->error("Erro de busca na API da Seplag. Inscrição ID {$d['id']}");
        continue;
      }
      

      $result = !isset($response) ? 10: 2;
    
      $evaluation_data_obs = !isset($response) ? $now: "$now | Descumpriu o DECRETO Nº33.953, de 25 de fevereiro de 2021. ART.3 INCISO IV - Não exercerem, a qualquer título, cargo, emprego ou função pública em quaisquer das esferas de governo";
      
      $registration = $app->repo("Registration")->find($d["id"]);

      $evaluation = new RegistrationEvaluation();

      $evaluation->id = $d["exists"];
      $evaluation->registration = $registration;
      $evaluation->user = $user;
      $evaluation->result = $result;

      $evaluation->evaluationData = [
        "status" => $result,
        "obs" => $evaluation_data_obs
      ];

      $evaluation->status = 1;

      $app->em->persist($evaluation);
      $app->em->flush();

      $app->log->info("Avaliação realizada com sucesso! Inscrição ID {$d['id']}");
    } 

    $app->log->info("CRON executado com sucesso! $now");
  }

 

  /**
   * 
   */
  function auth() {
    $client = new Client();
    
    $api = $this->config['api_seplag']['auth'];

    $bodyJson = json_encode([
      'cpf' => $api["keys"]["cpf"],
      'password' => $api["keys"]["password"],
      'idSistema' => $api["keys"]["idSistema"]
    ]);

    try {
      $response = $client->post($api['URL'], [
        'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
        'body'    => $bodyJson
      ]);
    } catch (\Exception $e) {
      $this->token = null;
      return;
    }

    $response = json_decode($response->getBody(), true);

    if (isset($response) && $response['sucesso']) {
      $this->token = $response['token'];
    }
  }

  function search($cpf) {
    $client = new Client([
      'verify' => false
    ]);

    $api = $this->config['api_seplag']['search'];

    try {
      $response = $client->request($api['method'], "{$api['URL']}?numeroDocumento=$cpf", [
        'headers' => [
          'Content-Type' => 'application/json', 
          'Accept' => 'application/json',
          'Authorization' => "Bearer {$this->token}"
        ]
      ]);
    } catch (\Exception $e) {
      throw $e;
    }

    return json_decode($response->getBody(), true);
  }

  public function register() {
    $app = App::i();

    $app->registerController('evaluate', 'EvaluationMethodSeplag\Controllers\Evaluate');
  }
}