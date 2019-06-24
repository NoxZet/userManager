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
    
    /**
     * Grabs current user and verifies their identity.
     * Sets template variables userId and username from identity if identity is valid.
     * @return bool True if current user is logged in and their identity matches data in db, false otherwise
     */
    public function validateIdentity(): bool {
        $user = $this->getUser();
        $identity = $user->getIdentity();
        if ($user->isLoggedIn() && $this->credentialManager->isIdentityValid($identity)) {
            $this->template->username = $identity->name;
            $this->template->userId = $identity->id;
            return true;
        }
        return false;
    }
    
    /**
     * If user is not logged in, they are redirected to Homepage:
     * If user is logged in but the identity doesn't match data in db, redirected to Log:out
     */
    public function validateRedirect(): void {
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect("Homepage:");
        }
        if (!$this->validateIdentity()) {
            $this->redirect("Log:out");
        }
    }
    
    /**
     * Renders a list of users with delete and edit options
     * Redirects if the identity is invalid
     */
    public function renderDefault(): void {
        $this->validateRedirect();
        $this->template->users = $this->credentialManager->getUsers();
    }
    
    /**
     * Renders a page for creating new user
     * Redirects if there is at least one user existing and identity is invalid
     */
    public function actionCreate(): void {
        if (!$this->credentialManager->isEmpty()) {
            $this->validateRedirect();
        }
        $this->template->isEmpty = $this->credentialManager->isEmpty();
    }
    
    /**
     * Renders a page for editing user credentials
     * Redirects if the identity is invalid
     */
    public function actionEdit(int $id): void {
        $this->validateRedirect();
        
        try {
            $name = $this->credentialManager->getUserName($id);
        } catch (App\Database\IdInvalidException $e) {
            $this->redirect("User:");
        }
        
        $this->template->editId = $id;
        $this["createUserForm"]->setDefaults(["name" => $name]);
        $this->template->isEmpty = $this->credentialManager->isEmpty();
    }
    
    /**
     * Deletes user with given id
     */
    public function actionDelete(int $id): void {
        $this->validateRedirect();
        
        try {
            $name = $this->credentialManager->deleteUser($id);
        } catch (App\Database\IdInvalidException $e) {
        }
        $this->redirect("User:");
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
        if (!$this->credentialManager->isEmpty()) {
            $this->validateRedirect();
        }
        try {
            $this->credentialManager->userCreate($values->name, $values->pass);
            $this->redirect("User:");
        } catch (App\Database\NameTakenException $e) {
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
        $this->validateRedirect();
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
