<?php
/**
 * Created by PhpStorm.
 * User: Peter
 * Date: 19. 5. 2018
 * Time: 21:00
 */

namespace App\Presenters;

use Nette;


class TeacherPresenter extends Nette\Application\UI\Presenter
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
            ->order('priezvisko ASC')->where('rola',  2);
    }

}