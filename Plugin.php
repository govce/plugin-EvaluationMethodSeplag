<?php 

namespace EvaluationMethodSeplag;

use MapasCulturais\App;
use MapasCulturais\i;

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

  public function register() {
    $app = App::i();

    $app->registerController('evaluate', 'EvaluationMethodSeplag\Controllers\Evaluate');
  }
}