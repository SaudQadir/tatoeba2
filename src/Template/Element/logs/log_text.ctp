<?php
use App\Lib\LanguagesLib;

$type = 'sentence';
if (isset($log->type)) {
    $type = $log->type;
}

$sentenceLink = '';
if ($log->sentence_id) {
    $sentenceId = $log->sentence_id;
    $sentenceLink = $this->Html->link(
        '#'.$sentenceId,
        array(
            'controller' => 'sentences',
            'action' => 'show',
            $sentenceId
        )
    );
}

$sentenceText = $log->text;

$translationLink = null;
if ($log->translation_id) {
    $translationId = $log->translation_id;
    $translationLink = $this->Html->link(
        '#'.$translationId,
        array(
            'controller' => 'sentences',
            'action' => 'show',
            $translationId
        )
    );
}

$langCode = $log->sentence_lang;
$langDir = LanguagesLib::getLanguageDirection($langCode);

$action = $log->action;
$username = $log->user ? $log->user->username : null;
$sentenceDate = $log->datetime;
$infoLabel = $this->Logs->getInfoLabel($type, $action, $username, $sentenceDate);

?>
<div class="md-list-item-text" layout="column">
    <div class="content" dir="<?= $langDir ?>">
        <?php
        if ($type =='sentence') {
            if (isset($withSentenceLink)) {
                echo $sentenceLink.' ';
            }
            echo h($sentenceText);
        } elseif ($type == 'license') {
            if (isset($withSentenceLink)) {
                echo $sentenceLink.' ';
            }
            echo ' ➜ '.$this->Html->tag('span', $sentenceText, array('class' => 'license'));
        } else { // link
            echo $sentenceLink.' ➜ '.$translationLink;
        }
        ?>
    </div>
    <p><?= $infoLabel ?></p>
</div>
