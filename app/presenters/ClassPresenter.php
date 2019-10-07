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

class ClassPresenter extends Nette\Application\UI\Presenter
{
    /** @var Nette\Database\Context */
    private $database;

    public function __construct(Nette\Database\Context $database)
    {
        $this->database = $database;
    }

    function renderList(){
        if(!$this->user->isLoggedIn()){
            $this->redirect('User:login');
            return;
        }

        $classes = $this->database->table('classes');
        $this->template->classes = $classes;

    }

    function renderDetail($id) {
        if(!$this->user->isLoggedIn()){
            $this->redirect('User:login');
            return;
        }

        $class = $this->database->table('classes')
            ->where('id', $this->getParameter('id'))->fetch();

        if(!$class) {
            return $this->redirect('Class:list');
        }

        $this->template->class = $class;

        $students = $this->database->query('SELECT * from users u JOIN userclasses uc ON uc.userId = u.id WHERE u.rola = 0 AND uc.classId = '.$id.';')->fetchAll();
        $this->template->students = $students;
    }

    function renderCreate(){
        if(!$this->user->isLoggedIn() || $this->user->getIdentity()->rola < 10){
            $this->redirect('User:login');
        }
    }

    function renderEdit($id){
        if(!$this->user->isLoggedIn() || $this->user->getIdentity()->rola < 10){
            $this->redirect('User:login');
        }

        if (!isset($id)) {
            $this->reditect('Class:list');
            return;
        }

    }

    protected function createComponentEditClassForm()
    {
        $class = $this->database->table('classes')
            ->where('id', $this->getParameter('id'))->fetch();

        if (!$class) {
            $this->reditect('Class:list');
            return;
        }

        $form = new UI\Form;
        $form->addText('name', 'Meno:')->isRequired();
        $form->addText('teacher', 'Učiteľ:');
        $form->addSubmit('update', 'Upraviť');
        $form->addHidden('id');

        $form->setDefaults($class);

        $form->onSuccess[] = [$this, 'editClassFormSuccess'];

        return $form;
    }

    protected function createComponentCreateClassForm()
    {
        $teachers = $this->database->table('users')
            ->order('priezvisko DESC')->where('rola',  2);

        $values = [];
        foreach ($teachers as $teacher) {
            $values[$teacher->id] = $teacher->meno.' '.$teacher->priezvisko;
        }

        $form = new UI\Form;
        $form->addText('name', 'Meno:')->isRequired();
        $form->addSelect('teacher', 'Učiteľ:', $values)->setAttribute('class', 'select-teacher');
        $form->addSubmit('create', 'Vytvoriť');

        $form->onSuccess[] = [$this, 'createClassFormSuccess'];

        return $form;
    }

    public function editClassFormSuccess(UI\Form $form, $values)
    {
        //getne tridu z db podľa id
        $row = $this->database->table('classes')
            ->where('id', $values->id)->fetch();

        if (!$row) {
            $this->flashMessage('Trieda neexistuje.');
            return;
        }

        $this->database->query('UPDATE classes SET ?', [
            'name' => $values->name,
            'teacher' => $values->teacher,
        ], 'WHERE id = ?', $values->id);

        $this->flashMessage('Trieda bola upravená.', 'success');
    }

    public function createClassFormSuccess(UI\Form $form, $values)
    {
        //getne tridu z db podľa mena
        $row = $this->database->table('classes')
            ->where('name', $values->name)->fetch();

        if ($row) {
             $this->flashMessage('Trieda už existuje.');
             return;
        }

        $this->database->query('INSERT INTO classes ?', [
            'name' => $values->name,
            'teacher' => $values->teacher,
        ]);

        $this->flashMessage('Trieda bola vytvorená.', 'success');
    }
}