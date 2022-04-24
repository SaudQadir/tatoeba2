<?php
/**
 * Tatoeba Project, free collaborative creation of multilingual corpuses project
 * Copyright (C) 2016 Gilles Bedel
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
 */
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use App\Lib\LanguagesLib;
use App\Model\CurrentUser;

class AudioController extends AppController
{
    public $name = 'Audio';

    public $uses = array(
        'Audio',
        'Language',
        'User',
        'CurrentUser'
    );

    public $components = array(
        'Flash'
    );

    public $helpers = array(
        'Pagination',
        'Languages',
        'Audio',
    );

    public $paginate = [
        'limit' => 100,
    ];

    public function beforeFilter(Event $event)
    {
        $this->Security->config('unlockedActions', [
            'save',
        ]);

        return parent::beforeFilter($event);
    }

    public function import() {
        $this->loadModel('Audios');
        $filesImported = $errors = false;
        if ($this->request->is('post')) {
            $author = $this->request->getData('audioAuthor');
            $filesImported = $this->Audios->importFiles($errors, $author);
        }
        $filesToImport = $this->Audios->getFilesToImport();

        $this->set(compact('filesToImport', 'errors', 'filesImported'));
    }

    public function index($lang = null) {
        $this->loadModel('Sentences');
        $query = $this->Sentences
            ->find()
            ->distinct('Sentences.id')
            ->innerJoinWith('Audios', function ($q) {
                return $q->contain('Users', function ($q) {
                             return $q->select(['username']);
                         });
            })
            ->contain('Audios')
            ->contain('Transcriptions')
            ->order(['Audios.modified' => 'DESC']);

        if (LanguagesLib::languageExists($lang)) {
            $query = $query->where(compact('lang'));
            $this->set(compact('lang'));
        }
        $sentencesWithAudio = $this->paginate($query);
        $this->set(compact('sentencesWithAudio'));
        
        $this->loadModel('Languages');
        $this->set(array('stats' => $this->Languages->getAudioStats()));
    }

    public function of($username) {
        $this->loadModel('Users');
        $userId = $this->Users->getIdFromUsername($username);
        if ($userId) {
            $this->loadModel('Sentences');
            $baseQuery = $this->Sentences
                ->find()
                ->innerJoinWith('Audios', function ($q) use ($userId) {
                    return $q->where(['Audios.user_id' => $userId])
                             ->contain('Users', function ($q) {
                                 return $q->select(['username']);
                             });
                })
                ->contain('Audios', function ($q) use ($userId) {
                    return $q->where(['Audios.user_id' => $userId]);
                })
                ->contain('Transcriptions')
                ->order(['Audios.modified' => 'DESC']);

            $audioCountQuery = clone $baseQuery;
            $this->set('totalAudio', $audioCountQuery->count());

            $query = $baseQuery->distinct('Sentences.id');
            $sentencesWithAudio = $this->paginate($query);
            $this->set(compact('sentencesWithAudio'));

            $audioSettings = $this->Users->getAudioSettings($userId);
            $this->set(compact('audioSettings'));
        }
        $this->set(compact('username'));
    }

    public function save_settings() {
        if (!empty($this->request->data)) {
            $currentUserId = CurrentUser::get('id');
            $allowedFields = array(
                'audio_license',
                'audio_attribution_url',
            );
            $dataToSave = $this->filterKeys($this->request->data, $allowedFields);
            $this->loadModel('Users');
            $user = $this->Users->get($currentUserId);
            $this->Users->patchEntity($user, $dataToSave);
            if ($this->Users->save($user)) {
                $flashMsg = __('Your audio settings have been saved.');
            } else {
                $flashMsg = __(
                    'An error occurred while saving. Please try again or '.
                    'contact us to report this.',
                    true
                );
            }
            $this->Flash->set($flashMsg);
        }

        $this->redirect(array('action' => 'of', CurrentUser::get('username')));
    }

    public function download($id) {
        $audio = false;

        $this->loadModel('Audios');
        try {
            $audio = $this->Audios->get($id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            if (CurrentUser::isAdmin()) {
                $this->loadModel('DisabledAudios');
                try {
                    $audio = $this->DisabledAudios->get($id);
                } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
                }
            }
        }

        if ($audio) {
            return $this->getResponse()
                        ->withFile($audio->file_path, ['download' => true]);
        } else {
            throw new \Cake\Http\Exception\NotFoundException();
        }
    }

    public function save($id) {
        $this->viewBuilder()->autoLayout(false);

        if ($this->request->is('post')) {
            $audio = false;
            $this->loadModel('Audios');
            try {
                $audio = $this->Audios->get($id);
            } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
                if (CurrentUser::isAdmin()) {
                    $this->loadModel('DisabledAudios');
                    try {
                        $audio = $this->DisabledAudios->get($id);
                    } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
                    }
                }
            }

            if ($audio) {
                $fields = $this->request->input('json_decode', true);
                $source = $audio->getSource();
                $this->{$source}->edit($audio, $fields);
                if ($this->{$source}->save($audio)) {
                    return $this->response->withStringBody(''); // OK
                }
            }
            throw new \Cake\Http\Exception\NotFoundException();
        }

        throw new \Cake\Http\Exception\BadRequestException();
    }
}
