<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use App;

final class LogPresenter extends Nette\Application\UI\Presenter {
    /** @var Nette\Database\Context */
    private $database;
    /** @var App\Database\DbCredentialManager */
    private $credentialManager;
    
    public function __construct(Nette\Database\Context $database, App\Database\DbCredentialManager $credentialManager) {
        $this->database = $database;
        $this->credentialManager = $credentialManager;
    }
    
    public function actionIn() {
        if ($this->getUser()->isLoggedIn()) {
            $this->redirect('User:');
        }
    }
    
    public function actionOut() {
        if ($this->getUser()->isLoggedIn()) {
            $this->getUser()->logout();
        }
        $this->redirect('Homepage:');
    }
    
    public function createComponentSignInForm(): Form {
        $form = new Form;
        $form->addText("name", "Username:")->setRequired("Please enter username");
        $form->addPassword("pass", "Password:")->setRequired("Please enter password");
        $form->addSubmit("send", "Sign in");
        $form->onSuccess[] = [$this, "signInFormSuccess"];
        return $form;
    }
    
    public function signInFormSuccess(Form $form, \stdClass $values): void {
        $user = $this->getUser();
        $user->setAuthenticator($this->credentialManager);
        
        try {
            $user->login($values->name, $values->pass);
            $this->redirect("User:");
        } catch (Nette\Security\AuthenticationException $e) {
            $form->addError('Invalid username and password combination');
        }
    }
    
}
