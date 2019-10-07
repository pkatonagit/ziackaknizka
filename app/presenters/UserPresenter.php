<?php
/**
 * Created by PhpStorm.
 * User: Peter
 * Date: 8. 5. 2018
 * Time: 20:38
 */

namespace App\Presenters;

use Nette;
use Nette\Application\UI;


class UserPresenter extends Nette\Application\UI\Presenter
{
    /** @var Nette\Database\Context */
    private $database;

    public function __construct(Nette\Database\Context $database)
    {
        $this->database = $database;
    }

    function renderLogin(){
        $this->template->disableNavigation = true;
    }

    function renderList() {
        $this->template->users = $this->database->table('users')
            ->order('priezvisko DESC')->where('rola',  0);
    }


    function renderEdit($id) {
        if(!$this->user->isLoggedIn()){
            $this->redirect('User:login');
        }

        if($this->user->getIdentity()->id != $id && $this->user->getIdentity()->rola < 2){
            $this->redirect('Homepage:default');
        }



        $loadedUser = $this->database->table('users')
            ->where('id', $this->getParameter('id'))->fetch();

        if(!$loadedUser){
            $this->redirect('Homepage:default');
            return;
        }

        $this->template->loadedUser = $loadedUser;

    }

    function renderPassword(){
        if(!$this->user->isLoggedIn()){
            $this->redirect('User:login');
        }
    }




    protected function createComponentEditSubjects()
    {
        $usersubjects = $this->database->query('SELECT * from subjects s JOIN usersubjects us ON s.id = us.subjectId WHERE us.userId = '.($this->getParameter('id')).';' )->fetchAll();

        $form = new UI\Form;

        $values = [];

        foreach($usersubjects as $t) {
            $form->addText($t->subjectId, $t->name);
            $values[$t->subjectId] = $t->znamky;
        }

        $form->addHidden('userId', $this->getParameter('id'));
        $form->addSubmit('editSubjects', 'Uložiť zmeny v známkach');
        $form->onSuccess[] = [$this, 'editUserSubjects'];

        $form->setDefaults($values);

        return $form;
    }

    protected function createComponentChangePasswordForm(){

        $form = new UI\Form();
        $form->addPassword('oldPassword', 'Pôvodné heslo:')->setRequired('Pôvodné heslo musí byť zadané');
        $form->addPassword('newPassword', 'Nové heslo:')->setRequired('Nové heslo musí byť zadané');
        $form->addPassword('repeatPassword', 'Opakovať nové heslo:')->setRequired('Pole je povinné');
        $form->addSubmit('changePassword', 'Zmeniť heslo');
        $form->onSuccess[] = function(UI\Form $f){
            $values = $f->getValues();
            $id = $this->user->getIdentity()->id;
            $user = $this->database->table('users')
                ->where('id', $id)->fetch();
            if(!$user){
                return $this->flashMessage('Používateľ sa nenašiel');
            }
            if(md5($values->oldPassword) != $user->heslo){
                $this->flashMessage('Heslá sa nezhodujú');
                return;
            }
            if($values->newPassword != $values->repeatPassword){
                $this->flashMessage('Heslá sa nezhodujú');
                return;
            }
            $pole= array(
                'heslo' => md5($values->newPassword)
            );

            $this->database->query('UPDATE users SET ?', $pole, 'WHERE id = ?', $id);
            $this->flashMessage('Heslo bolo zmenené');
        };
        return $form;
    }

    protected function createComponentEditUserForm()
    {
        $user = $this->database->table('users')
            ->where('id', $this->getParameter('id'))->fetch();

        $subjects = $this->database->table('subjects');

        $usersubjects = $this->database->table('usersubjects')
            ->where('userId', $this->getParameter('id'));

        $userclass = $this->database->table('userclasses')
            ->where('userId', $this->getParameter('id'))->fetch();

        if (!$user) {
            $this->redirect('Homepage:default');
            return;
        }

        if($this->user->getIdentity()->id != $user->id && $this->user->getIdentity()->rola <= $user->rola){
            $this->redirect('Homepage:default');
            return;
        }

        $temp = array();

        foreach ($subjects as $subject) {
            $temp[$subject->id] = $subject->name;
        }

        $form = new UI\Form;
        $form->addText('meno', 'Meno:')->isRequired('Meno je povinné');
        $form->addText('priezvisko', 'Priezvisko')->isRequired('Priezvisko je povinné');
        $form->addEmail('email', 'Email')->isRequired('Email je povinný');
        if($this->user->getIdentity()->rola > 9) {
            $form->addText('rola', 'Rola')->isRequired('Rola je povinná');
        }


        if($this->user->getIdentity()->rola > 1 && $user->rola < 2) {
            $form->addMultiSelect('subjects', 'Predmety', $temp)->setAttribute('class', 'select-subjects');
            $form->addText('trieda', 'Trieda');
        }

        $form->addSubmit('edit', 'Upraviť');
        $form->addHidden('id');


        $s = [];
        foreach ($usersubjects as $subject) {
            array_push($s, $subject->subjectId);
        }

        $values= array(
            'id' => $user->id,
            'meno' => $user->meno,
            'priezvisko' => $user->priezvisko,
            'email' => $user->email,
            'subjects' => $s,
            'rola' => $user->rola
        );

        if ($userclass) {
            $values['trieda'] = $userclass->classId;
        }

        $form->setDefaults($values);

        $form->onSuccess[] = [$this, 'editUserSuccess'];

        return $form;
    }


    public function editUserSuccess(UI\Form $form, $values)
    {
        //getne tridu z db podľa id
        $user = $this->database->table('users')
            ->where('id', $values->id)->fetch();
        $subjects = $this->database->table('usersubjects')
            ->where('userId', $values->id);

        if (!$user) {
            $this->flashMessage('Použivateľ neexistuje.');
            return;
        }

        if ($form->isSuccess()) {
            $val = array(
                'meno' => $user->meno,
                'priezvisko' => $user->priezvisko,
                'email' => $user->email,
            );

            if(isset($values->rola)) {
                $val['rola'] = $values->rola;
            }


            if($this->user->getIdentity()->rola > 1 && $user->rola < 2) {

                $this->database->query('UPDATE users SET ?', $val, 'WHERE id = ?', $values->id);

                $this->database->query('DELETE FROM usersubjects WHERE userId = ?', $values->id);

                foreach ($values->subjects as $subjectId) {

                    $subval = [
                        'userId' => $values->id,
                        'subjectId' => $subjectId,
                    ];

                    $found = null;
                    foreach($subjects as $struct) {
                        if ($subjectId == $struct->id) {
                            $found = $struct;
                            break;
                        }
                    }

                    if ($found) {
                        $subval['znamky'] = $found->znamky;
                    }

                    $this->database->query('INSERT INTO usersubjects ?', $subval);
                }

                if ($values->trieda != "") {
                    $this->database->query('DELETE FROM userclasses WHERE userId = ?', $values->id);
                    $this->database->query('INSERT INTO userclasses ?', [
                        'userId' => $user->id,
                        'classId' => $values->trieda
                    ]);
                }

            }
            $this->flashMessage('Používateľ bol upravený.', 'success');
        }
    }


    public function editUserSubjects(UI\Form $form, $values)
    {
        $id = $values->userId;
        $this->database->query('DELETE FROM usersubjects WHERE userId = ?', $id);

        foreach ($values as  $key => $value) {
            if($key != 'userId') {
                $subval = [
                    'znamky' => $value,
                    'subjectId' => $key,
                    'userId' => $id
                ];
                $this->database->query('INSERT INTO usersubjects ?', $subval);
            }
        }

        $this->flashMessage('Znamky boli upravené.', 'success');
    }


    protected function createComponentRegistrationForm()
    {
        $form = new UI\Form;
        $form->addText('login', 'Login:');
        $form->addPassword('password', 'Heslo:');
        $form->addSubmit('prihlasenie', 'Prihlásit');
        $form->onSuccess[] = [$this, 'loginFormSucceeded'];
        return $form;
    }


    public function loginFormSucceeded(UI\Form $form, $values)
    {

        try{
            $this->user->login($values->login, $values->password);
            $this->user->setExpiration('20 minutes', true);
            $this->flashMessage('Boli ste úspešne prihlásený.');
            $this->redirect('Homepage:');

        }catch (Nette\Security\AuthenticationException $exception){
            $this->flashMessage($exception->getMessage(), 'error');

        }
    }
    function renderLogout(){
        $this->user->logout(true);
        $this->flashMessage('Boli ste úspešne odhlásený.');
        $this->redirect('User:login');
    }
}