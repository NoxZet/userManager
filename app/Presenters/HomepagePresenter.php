<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use App;

final class HomepagePresenter extends Nette\Application\UI\Presenter {
    /** @var Nette\Database\Context */
    private $database;
    /** @var \App\Database\DbCredentialManager */
    private $credentialManager;
    
    public function __construct(Nette\Database\Context $database, App\Database\DbCredentialManager $credentialManager) {
        $this->database = $database;
        $this->credentialManager = $credentialManager;
    }
    
    public function actionDefault(): void {
        if ($this->getUser()->isLoggedIn()) {
            $this->redirect("User:");
        }
        $this->template->isEmpty = $this->credentialManager->isEmpty();
    }
    
}
