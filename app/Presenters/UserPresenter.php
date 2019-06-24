<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use App;

final class UserPresenter extends Nette\Application\UI\Presenter {
    /** @var Nette\Database\Context */
    private $database;
    /** @var \App\Database\DbCredentialManager */
    private $credentialManager;
    
    public function __construct(Nette\Database\Context $database, App\Database\DbCredentialManager $credentialManager) {
        $this->database = $database;
        $this->credentialManager = $credentialManager;
    }
    
    public function renderDefault(): void {
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect("Homepage:");
        }
        $this->template->users = $this->credentialManager->getUsers();
    }
    
    public function actionCreate(): void {
        if (!$this->getUser()->isLoggedIn() && !$this->credentialManager->isEmpty()) {
            $this->redirect("Homepage:");
        }
        $this->template->isEmpty = $this->credentialManager->isEmpty();
    }
    
    public function actionEdit(int $id): void {
        if (!$this->getUser()->isLoggedIn() || $this->credentialManager->isEmpty()) {
            $this->redirect("Homepage:");
        }
        
        try {
            $name = $this->credentialManager->getUserName($id);
        } catch (App\Database\IdInvalidException $e) {
            $this->redirect("User:");
        }
        
        $this["createUserForm"]->setDefaults(["name" => $name]);
        $this->template->isEmpty = $this->credentialManager->isEmpty();
    }
    
    /**
     * 
     */
    public function actionDelete(int $id): void {
        if (!$this->getUser()->isLoggedIn() || $this->credentialManager->isEmpty()) {
            $this->redirect("Homepage:");
        }
        
        try {
            $name = $this->credentialManager->deleteUser($id);
            $this->redirect("User:");
        } catch (App\Database\IdInvalidException $e) {
            $this->redirect("User:");
        }
    }
    
    /**
     * Creates a form for creating a new user
     */
    public function createComponentCreateUserForm(): Form {
        $form = new Form;
        $form->addText("name", "Username:")->setRequired("Please enter username");
        $form->addPassword("pass", "Password:")->setRequired("Please enter password");
        $form->addSubmit("send", "Create");
        $form->onSuccess[] = [$this, "createUserFormSuccess"];
        return $form;
    }
    
    /**
     * Creates a user on create form submission
     */
    public function createUserFormSuccess(Form $form, \stdClass $values): void {
        if (!$this->getUser()->isLoggedIn() && !$this->credentialManager->isEmpty()) {
            $this->redirect("Homepage:");
        }
        try {
            $this->credentialManager->userCreate($values->name, $values->pass);
            $this->redirect("User:");
        } catch (App\Database\UserCreateException $e) {
            $form->addError("Can't create user: ".$e);
            $this->redirect("User:");
        }
    }
    
    /**
     * Creates a form for editing user's credentials
     */
    public function createComponentEditUserForm(): Form {
        $form = new Form;
        $form->addText("name", "Username:");
        $form->addPassword("pass", "Password:");
        $form->addSubmit("send", "Edit");
        $form->onSuccess[] = [$this, "editUserFormSuccess"];
        return $form;
    }
    
    /**
     * Applies changes of credentials on edit form submission
     */
    public function editUserFormSuccess(Form $form, \stdClass $values): void {
        if (!$this->getUser()->isLoggedIn() || $this->credentialManager->isEmpty()) {
            $this->redirect("Homepage:");
        }
        $id = (int)$this->getParameter("id");
        if (!$id) {
            $this->redirect("User:");
        }
        try {
            $this->credentialManager->changeCredentials($id, $values->name, $values->pass);
            $this->redirect("User:");
        } catch (App\Database\IdInvalidException $e) {
            $form->addError("Can't edit user: ".$e);
            $this->redirect("User:");
        }
    }
    
}
