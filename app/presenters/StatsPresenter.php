<?php
/**
 * Created by PhpStorm.
 * User: Peter
 * Date: 25. 5. 2018
 * Time: 9:17
 */

namespace App\Presenters;
use Nette;
use Nette\Application\UI;

class StatsPresenter extends Nette\Application\UI\Presenter
{

    /** @var Nette\Database\Context */
    private $database;

    public function __construct(Nette\Database\Context $database)
    {
        $this->database = $database;
    }

    function renderDefault(){

        $classes = $this->database->query('SELECT * from classes;')->fetchAll();


        foreach ($classes as $class) {
            $studentsCount = $this->database->query('SELECT count(id) as pocetStudentov from users u JOIN userclasses uc ON uc.userId = u.id WHERE uc.classId = '.$class->id.';')->fetch();
            $class->pocetStudentov = $studentsCount->pocetStudentov;

         /*  $znamkyRes = $this->database->query('SELECT avg(znamky) as priemerZnamky from users u JOIN usersubjects us ON us.u WHERE us.classId = '.$class->id.';')->fetchAll();
           $class->priemerZnamky = $znamkyRes->priemerZnamky;*/


        }

        $this->template->classes = $classes;
    }

    function renderStats($id) {

        $class = $this->database->table('classes')
            ->where('id', $this->getParameter('id'))->fetch();

        if(!$class) {
            return $this->redirect('Stats:stats');
        }

        $this->template->class = $class;

        $students = $this->database->query('SELECT COUNT(*) from users u JOIN userclasses uc ON uc.userId = u.id WHERE u.rola = 0 AND uc.classId = '.$id.';')->fetchAll();
        $this->template->students = $students;
    }
}