<?php 

namespace EvaluationMethodSeplag;

require __DIR__ . '/vendor/autoload.php';

use MapasCulturais\App;
use MapasCulturais\i;
use GuzzleHttp\Client;
use MapasCulturais\Entities\RegistrationEvaluation;

class Plugin extends \MapasCulturais\Plugin {
  
  public function __construct(array $config = [])
  {
    $this->config["cron_sql_limit"] = isset($this->config["cron_sql_limit"]) ? $this->config["cron_sql_limit"] : 50;
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
    
    $app = App::i(); 
    $seplagApi = new \EvaluationMethodSeplag\SeplagAPI($this->config);

    $date_time_now = date('d-m-Y H:i:s');   

    if (!$seplagApi->authenticate()) {      
      $app->log->error("Erro ao se autenticar com a SEPLAG!. Informe aos desenvolvedores.");
      echo 'Erro ao se autenticar com a SEPLAG!. Informe aos desenvolvedores.';
      return;
    }

    $sql = "
      SELECT
        r.id, 
        REPLACE(REPLACE(REPLACE(REPLACE(am.value, '.', '' ),'/',''),'-',''), '\"', '') AS cpf,
        re.id AS evaluation
      FROM 
        registration r 
        LEFT JOIN agent_meta am ON am.object_id = r.agent_id AND am.key = 'documento'
        LEFT JOIN registration_evaluation re ON re.registration_id = r.id AND user_id = {$this->config['user_id']}
      WHERE
        r.status = 1
        AND r.opportunity_id = {$this->config['opportunity_id']}
        AND re.id IS NULL
      LIMIT {$this->config['cron_sql_limit']};
      ";

    $stmt = $app->em->getConnection()->prepare($sql);
    $stmt->execute();
    $list= $stmt->fetchAll();

    foreach($list as $item) {
      $response_SEPLAG_API = null;
  
      try {
        $response_SEPLAG_API = $seplagApi->searchEmployeeByCPF($item["cpf"]);
      } catch (\Exception $e) {
        $app->log->error("Erro de busca na API da Seplag. Inscrição ID {$item['id']}");
        continue;
      }     
       
      $registration = $app->repo("Registration")->find($item["id"]);
      $user = $app->repo("User")->find($this->config["user_id"]);

      $evaluation_id = $item["evaluation"];
      $evaluation = empty($evaluation_id) ? new RegistrationEvaluation() : $app->repo("RegistrationEvaluation")->find($evaluation_id) ;
      $evaluation->registration = $registration;
      $evaluation->user = $user;

      $evaluation_result = !isset($response_SEPLAG_API) ? 10: 2;
      $evaluation_data_obs = !isset($response_SEPLAG_API) ? "Consultado na SEPLAG em $date_time_now": "Consultado na SEPLAG em  $date_time_now | Descumpriu o DECRETO Nº33.953, de 25 de fevereiro de 2021. ART.3 INCISO IV - Não exercerem, a qualquer título, cargo, emprego ou função pública em quaisquer das esferas de governo";
     
      $evaluation->result = $evaluation_result;      
      $evaluation->evaluationData = ["status" => $evaluation_result, "obs" => $evaluation_data_obs];
      $evaluation->setStatus(1);
      $evaluation->save(true);

      $app->log->info("Avaliação realizada com sucesso! Inscrição ID {$item['id']}");
    } 

    $app->log->info("CRON executado com sucesso! $date_time_now");
  }

  public function register() {
    $app = App::i();

    $app->registerController('evaluate', 'EvaluationMethodSeplag\Controllers\Evaluate');
  }
}