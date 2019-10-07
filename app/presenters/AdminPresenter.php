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

class AdminPresenter extends Nette\Application\UI\Presenter
{
    /** @var Nette\Database\Context */
    private $database;

    public function __construct(Nette\Database\Context $database)
    {
        $this->database = $database;
    }

    function renderUser(){
        if(!$this->user->isLoggedIn() || $this->user->getIdentity()->rola < 10){
            $this->redirect('User:login');
        }

    }

    function renderClass(){
        if(!$this->user->isLoggedIn() || $this->user->getIdentity()->rola < 10){
            $this->redirect('User:login');
        }

    }

    protected function createComponentUserForm()
    {
        $form = new UI\Form;
        $form->addText('name', 'Meno:');
        $form->addText('surname', 'Priezvisko:');
        $form->addText('email', 'Email:');
        $form->addText('rola', 'Rola:');
        $form->addText('login', 'Login:');
        $form->addPassword('password', 'Heslo:');
        $form->addText('trieda', 'Trieda');
        $form->addSubmit('register', 'Registrovať');
        $form->onSuccess[] = [$this, 'createUserFormSucceeded'];
        return $form;
    }


    public function createUserFormSucceeded(UI\Form $form, $values)
    {
        $this->database->query('INSERT INTO users ?', [
            'meno' => $values->name,
            'priezvisko' => $values->surname,
            'email' => $values->email,
            'heslo' => md5($values->password),
            'login' => $values->login,
            'rola' => $values->rola,
        ]);

        $user = $this->database->query('SELECT * from users WHERE email ="'.$values->email.'";')->fetch();

        if ($user && $values->trieda != "") {
            $this->database->query('INSERT INTO userclasses ?', [
                'userId' => $user->id,
                'classId' => $values->trieda
            ]);
        }

        $this->flashMessage('POUZIVATEL BOL REGISTROVANY', 'success');
    }
}