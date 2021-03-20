<?php 
use MapasCulturais\i;

$route = MapasCulturais\App::i()->createUrl('evaluate', 'run');

?>

<a class="btn btn-default download btn-export-cancel"  ng-click="editbox.open('form-parameters-SEPLAG', $event)" rel="noopener noreferrer">Avaliador SEPLAG</a>

<!-- Formulário -->
<edit-box id="form-parameters-SEPLAG" position="top" title="<?php i::esc_attr_e("Formulário de Avaliação da SEPLAG") ?>" cancel-label="Cancelar" close-on-cancel="true">
    <form class="form-export-dataprev" action="<?=$route?>" method="POST">        
        <div>
            <b>As inscrições devem ser reavaliadas?</b> <br>      
            <input type="radio" name="areReassessed" value="1" title=""> Sim <br>
            <input type="radio" name="areReassessed" value="0" checked title=""> Não<br> 

            <b>Quem deve ser avaliado?</b> <br>  
            <input type="radio" name="formEvaluation" value="all" title="" checked> Todos</br>
            <input type="radio" name="formEvaluation" value="selected" title=""> Somente os que estão listados abaixo</br>
            <textarea name="listSelected" id="listSelected" cols="30" rows="2" placeholder="Separe por ponto e virgula e sem prefixo Ex.: 1256584;6941216854"></textarea> 
        </div>

        <input type="hidden" name="opportunity" value="<?= $opportunity->id ?>">
        <button class="btn btn-primary download" name="evaluate" value="evaluate" type="submit">Avaliar</button>
    </form>
</edit-box>