<?php

namespace App\Presenters;

use Nette;


class HomepagePresenter extends Nette\Application\UI\Presenter
{
    /** @var Nette\Database\Context */
    private $database;

    public function __construct(Nette\Database\Context $database)
    {
        $this->database = $database;
    }

    function renderDefault(){
        if($this->user->loggedIn) {
            $this->redirect('Ziaci:list');
        }else {
            $this->redirect('User:login');
        }
    }
}
