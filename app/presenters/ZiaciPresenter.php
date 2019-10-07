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


class ZiaciPresenter extends Nette\Application\UI\Presenter
{
    /** @var Nette\Database\Context */
    private $database;

    public function __construct(Nette\Database\Context $database)
    {
        $this->database = $database;
    }

    function renderList() {
        if(!$this->user->isLoggedIn()){
            $this->redirect('User:login');
            return;
        }
        $this->template->users = $this->database->table('users')
            ->order('priezvisko ASC')->where('rola',  0);
    }

    function renderGrades() {
        if(!$this->user->isLoggedIn()) {
            return $this->redirect('User:login');
        }


        $user = $this->database->table('users')
            ->order('priezvisko DESC')->where('id',  $this->user->getIdentity()->id)->fetch();

        if(!$user) {
            return $this->redirect('Homepage:default');
        }

        $this->template->student = $user;

        $subjects = $this->database->query('SELECT * FROM subjects s JOIN usersubjects us ON s.id = us.subjectId WHERE us.userId = '. $user->id. ';')->fetchAll();
        $this->template->subjects = $subjects;
    }
}