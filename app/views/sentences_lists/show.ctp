<?php
/**
 * Tatoeba Project, free collaborative creation of multilingual corpuses project
 * Copyright (C) 2009  HO Ngoc Phuong Trang <tranglich@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  Tatoeba
 * @author   HO Ngoc Phuong Trang <tranglich@gmail.com>
 * @license  Affero General Public License
 * @link     http://tatoeba.org
 */
 
$listId = $list['SentencesList']['id'];
$listName = $list['SentencesList']['name'];
$listOwnerId = $list['SentencesList']['user_id'];
$isAuthenticated = $session->read('Auth.User.id');
$isListPublic = ($list['SentencesList']['is_public'] == 1);
$belongsToUser = $session->read('Auth.User.id') == $listOwnerId;

$this->set('title_for_layout', $pages->formatTitle($listName));
?>

<div id="annexe_content">
    <div class="module">
        <h2><?php __('Information'); ?></h2>
        <?php
        $linkToAuthorProfile = $html->link(
            $list['User']['username'],
            array(
                'controller' => 'user',
                'action' => 'profile',
                $list['User']['username']
            )
        );
        $createdBy = format(
            __('created by {listAuthor}', true),
            array('listAuthor' => $linkToAuthorProfile)
        );
        if ($isListPublic) {
            $listType = __('Collaborative list', true);
        } else {
            $listType = __('Personal list', true);
        }
        $createdDate = $date->ago($list['SentencesList']['created']);
        echo $html->tag('p', $createdBy);
        echo $html->tag('p', $createdDate);
        echo $html->tag('p', $listType);
        ?>
    </div>

    <div class="module">
    <h2><?php __('Menu'); ?></h2>
    <ul class="sentencesListActions">
        <?php
        $lists->displayPublicActions(
            $listId, $translationsLang, 'show'
        );
        
        if ($belongsToUser) {
            $lists->displayRestrictedActions(
                $listId,
                'edit',
                $isListPublic
            );
        }
        ?>
    </ul>
    <?php
    if ($canDownload) {
        $lists->displayDownloadLink($listId);
    } else {
        echo $downloadMessage;
    }
    ?>
    </div>
    
</div>

<div id="main_content">
    <div class="module">
    <?php
    $class = '';
    if ($belongsToUser) {
        $javascript->link(JS_PATH . 'jquery.jeditable.js', false);
        $javascript->link(JS_PATH . 'sentences_lists.edit_name.js', false);

        $class = 'editable-list-name';

        $editImage = $this->Images->svgIcon(
            'edit',
            array(
                'alt'=> __('Edit', true),
                'title'=> __('Edit name', true),
                'width' => 16,
                'height' => 16
            )
        );
    }

    echo $html->tag('h2', $listName, array(
        'id'    => "l$listId",
        'class' => $class,
        'data-submit'  => __('OK', true),
        'data-cancel'  => __('Cancel', true),
        'data-tooltip' => __('Click to edit...', true),
    ));

    if ($belongsToUser) {
        echo $html->div('edit-list-name', $editImage);

        $javascript->link(JS_PATH . 'sentences_lists.remove_sentence_from_list.js', false);
        $lists->displayAddSentenceForm($listId);
    }

    $url = array($listId, $translationsLang);
    $pagination->display($url);

    ?>
    
    <div class="sentencesList" id="sentencesList">
    <?php
    foreach ($sentencesInList as $item) {
        $sentence = $item['Sentence'];
        $translations = array();
        if (!empty($sentence['Translation'])) {
            foreach ($sentence['Translation'] as $value) {
                $translations[] = array('Translation' => $value);
            }
        }
        $lists->displaySentence($sentence, $translations, $belongsToUser);
    }
    ?>
    </div>
    
    <?php
    $pagination->display($url);
    ?>
    
    </div>
</div>
