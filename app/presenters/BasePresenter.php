<?php

use Nette\Forms\Form;

abstract class BasePresenter extends Nette\Application\UI\Presenter
{

    /** hack for automated application testing
     *  avoids sending Content-Type header
     */
    static public $testMode = false;

    protected $pdf_output = false;
    
    public function getStorage()
    {
        return $this->context->getService('storage');
    }

    public function startup()
    {
        if (defined('APPLICATION_INSTALL')) {
            if (strncmp($this->name, 'Install', 7) != 0)
                $this->redirect(':Install:Default:default');
        }
        else {
            $user = $this->user;

            // Je uzivatel prihlasen?
            if (!$user->isLoggedIn()) {
                if ($user->getLogoutReason() === Nette\Security\User::INACTIVITY) {
                    $this->flashMessage('Uplynula doba neaktivity! Systém vás z bezpečnostních důvodů odhlásil.',
                            'warning');
                }
                if (!( $this->name == "Spisovka:Uzivatel" && $this->view == "login" )) {
                    // asession ID je pouzito jenom u SSO prihlasovani
                    $asession = $this->getParameter('_asession');
                    $alternative = $this->getParameter('alternativelogin');
                    $this->forward(':Spisovka:Uzivatel:login',
                            array('_asession' => $asession, 'alternativelogin' => $alternative));
                }
            } else {
                $acc = new UserAccount($user);
                if ($acc->force_logout) {
                    $user->logout();
                    $acc->force_logout = false;
                    $acc->save();

                    $this->flashMessage('Byl jste odhlášen administrátorem.', 'warning');
                    // Presmeruj, chceme pote uzivatel na vychozi/domovske strance
                    $this->redirect(':Spisovka:Uzivatel:login');
                }

                if ($this->name == "Spisovka:Uzivatel") {
                    // Tento presenter je vzdy pristupny
                    if ($this->view == "login")
                    // Uzivatel je prihlasen - login obrazovka je zbytecna, presmerujeme na uvodni obrazovku
                    // Oprava mozneho bugu s dvojim prihlasovanim = po prihlaseni se opet zobrazuje login obrazovka
                        $this->redirect(':Spisovka:Default:default');
                }
                else if (!$this->isUserAllowed()) {
                    // Uzivatel je prihlasen, ale nema opravneni zobrazit stranku
                    $this->forward(':NoAccess:default');
                }
            }
        }

        parent::startup();

        $this->translateFormRulesMessages();
    }

    protected function isUserAllowed()
    {
        return $this->user->isAllowed($this->reflection->name, $this->getAction());
    }

    protected function afterRender()
    {
        // Není nutné toto duplikovat pro každou funkci, která umožňuje tisk
        if ($this->getParameter('print') || $this->getParameter('pdfprint'))
            $this->setLayout('print');

        /* Následuje XHTML magie */
        $request = $this->getHttpRequest();
        $accept = $request->getHeader('Accept', '');
        $xhtml_browser = strpos($accept, 'application/xhtml+xml') !== false;
        $response = $this->getHttpResponse();

        try {
            $enable_xhtml = Settings::get('xhtml', false);
        } catch (Exception $e) {
            // ošetři výjimku při instalaci aplikace
            $e->getMessage();
            $enable_xhtml = false;
        }

        if ($enable_xhtml && $xhtml_browser && !$this->isAjax() && !self::$testMode)
            $response->setContentType('application/xhtml+xml', 'utf-8');
    }

    protected function beforeRender()
    {
        /** Toto jiz k nicemu neni. Spisovka pouziva standardne Tracy.
          if (DEBUG_ENABLE && in_array('programator', $this->user->getRoles())) {
          $this->template->debugger = TRUE;
          } else {
          $this->template->debugger = FALSE;
          }
         */
        // Nastaveni title
        if (!isset($this->template->title)) {
            $this->template->title = "";
        }

        // module : presenter : view
        $a = strrpos($this->name, ':');
        if ($a === FALSE) {
            $this->template->module = '';
            $this->template->presenter_name = $this->name;
        } else {
            $this->template->module = substr($this->name, 0, $a + 0);
            $this->template->presenter_name = substr($this->name, $a + 1);
        }

        /**
         * Nastaveni layoutu podle modulu
         */
        if ($this->template->module == 'Install' && $this->view == 'kontrola' && !defined('APPLICATION_INSTALL'))
            $this->template->module = 'Admin';   // specialni pripad

        if ($this->name == "Spisovka:Uzivatel" && $this->view == "login")
            $this->setLayout('login');
        else
            switch ($this->template->module) {
                case "Admin":
                    $this->setLayout('admin');
                    $this->template->module_name = 'Administrace';
                    break;
                case "Spisovna":
                    $this->setLayout('spisovna');
                    $this->template->module_name = 'Spisovna';
                    break;
                case "Epodatelna":
                    $this->setLayout('epodatelna');
                    $this->template->module_name = 'E-podatelna';
                    break;
                case "Spisovka":
                    $this->template->module_name = 'Spisová služba';

                    if ($this->name == "Spisovka:Napoveda") {
                        $this->setLayout('napoveda');
                        $this->template->module_name = 'Nápověda';
                    } else if ($this->name == "Spisovka:Zpravy")
                        $this->setLayout('zpravy');
                    else
                        $this->setLayout('spisovka');
                    break;
                case "Install":
                    $this->setLayout('install');
                    break;
            }

        // [P.L.] Slouží pouze jako pojistka proti případné chybě v šabloně
        // Ajax šablony nemají definovat žádný blok, pak se layout nepoužije
        if ($this->getHttpRequest()->isAjax())
            $this->setLayout(false);

        $helpUri = "napoveda/" . strtolower("{$this->template->module}/{$this->template->presenter_name}/{$this->view}");
        $this->template->helpUri = $helpUri;

        /**
         * Informace o Aplikaci
         */
        $this->template->KontrolaNovychVerzi = UpdateAgent::je_aplikace_aktualni();

        $this->template->baseUrl = $this->getHttpRequest()->getUrl()->getBasePath();
        $this->template->publicUrl = GlobalVariables::get('publicUrl');

        $this->template->licence = '<a href="http://joinup.ec.europa.eu/software/page/eupl/licence-eupl">EUPL v.1.1</a>';

        /**
         * Informace o Klientovi
         */
        $client_config = GlobalVariables::get('client_config');
        $this->template->Urad = $client_config->urad;

        /**
         * Uzivatel
         */
        $this->template->is_authenticated = false;
        $user = $this->user;
        $this->template->user = $user;
        if ($this->name != 'Error' && $user->isLoggedIn()) {
            $this->template->is_authenticated = true;

            /**
             * Upozorneni o zpravach uzivateli
             */
            $this->template->zpravy_pocet_neprectenych = 0;
            if ($user->isAllowed('Spisovka_ZpravyPresenter')) {
                // zjisti kolik ma uzivatel neprectenych zprav                
                $this->template->zpravy_pocet_neprectenych = Zpravy::dej_pocet_neprectenych_zprav();
            }
        }

        // Nastav, aby Nette generovalo ID prvku formulare jako ve stare verzi
        Nette\Forms\Controls\BaseControl::$idMask = 'frm%s';
    }

    public function templatePrepareFilters($template)
    {
        $latte = $template->getLatte();

        $set = new Latte\Macros\MacroSet($latte->getCompiler());
        $set->addMacro('css', 'echo \Spisovka\LatteMacros::CSS($publicUrl, %node.args);');
        $set->addMacro('js', 'echo \Spisovka\LatteMacros::JavaScript(%node.word, $publicUrl);');

        $set->addMacro('access', 'if (\Spisovka\LatteMacros::access($user, %node.word)) {', '}');
        $set->addMacro('isAllowed',
                'if (\Spisovka\LatteMacros::isAllowed($user, %node.args)) {', '}');
        $set->addMacro('isInRole', 'if (\Spisovka\LatteMacros::isInRole($user, %node.args)) {',
                '}');

        $set->addMacro('label2', 'echo \Spisovka\LatteMacros::label2($form, %node.args)');
        $set->addMacro('input2', 'echo \Spisovka\LatteMacros::input2($form, %node.args)');
        $set->addMacro('inputError2',
                'echo \Spisovka\LatteMacros::inputError2($form, %node.args)');

        /* Neni momentalne pouzito:
          // $set->addMacro('accessrole', '{', '}');

          $filter->handler->macros['accessrole'] =
          '<?php if ( Acl::isInRole("%%")) { ?>';
          $filter->handler->macros['/accessrole'] =
          '<?php } ?>'; */
    }

    protected function createTemplate($class = null)
    {
        $template = parent::createTemplate($class);

        \Spisovka\LatteFilters::register($template);

        return $template;
    }

    /**
     * Formats view template file names.
     * @return array
     */
    public function formatTemplateFiles()
    {
        $name = $this->getName();
        $dir = str_replace(':', 'Module/', $name);
        // $dir = is_dir("$dir/templates") ? $dir : dirname($dir);
        $templates = APP_DIR . '/templates';
        return array(
            "$templates/$dir/$this->view.latte",
            "$templates/$dir/$this->view.phtml",
        );
    }

    /**
     * Nahradí zprávy při chybě validace vlastními zprávami v češtině.
     */
    protected function translateFormRulesMessages()
    {
        $messages = [
            // Form::FILLED se použije pouze pro prohlížeče,
            // které neumí HTML5 validaci
            Form::FILLED => 'Vyplňte prosím toto pole.',
            Form::EMAIL => 'Zadejte prosím platnou e-mailovou adresu.',
            Form::INTEGER => 'Zadejte prosím celé číslo.',
            Form::FLOAT => 'Zadejte prosím číslo.'
        ];

        foreach ($messages as $id => $message)
            \Nette\Forms\Rules::$defaultMessages[$id] = $message;
    }

    protected function createComponentPrint()
    {
        $c = new Spisovka\Components\PrintControl();
        return $c;
    }

    protected function shutdown($response)
    {
        if ($this->getParameter('pdfprint') || $this->view == 'pdf' || $this->pdf_output) {
            ob_start();
            $response->send($this->getHttpRequest(), $this->getHttpResponse());
            $content = ob_get_clean();
            $this->pdfExport($content);
        }
    }

    protected function pdfExport($content)
    {
        @ini_set("memory_limit", PDF_MEMORY_LIMIT);

        // mPDF obsahuje spoustu chyb a omezení
        $search = ['<td', '</dd><br />', '<dd></dd>'];
        $replace = ['<td valign="top"', '</dd>', '<dd>&nbsp;</dd>'];
        $content = str_replace($search, $replace, $content);
        // nechceme v PDF vytvářet nesmyslné hypertextové odkazy
        $content = preg_replace('#href="[^"]*"#s', '', $content);

        $page_format = in_array($this->view, ["detail", "odetail", "printdetail"]) ? 'A4' : 'A4-L';
        // Poznamka: zde dany font se nepouzije, pouzije se font z CSS
        $mpdf = new mPDF('iso-8859-2', $page_format, 9, 'Helvetica');

        $app_info = new VersionInformation();
        $mpdf->SetCreator($app_info->name);
        $mpdf->SetAuthor($this->user->displayName);

        $mpdf->defaultheaderfontsize = 10; /* in pts */
        $mpdf->defaultheaderfontstyle = 'B'; /* blank, B, I, or BI */
        $mpdf->defaultheaderline = 1;  /* 1 to include line below header/above footer */
        $mpdf->defaultfooterfontsize = 9; /* in pts */
        $mpdf->defaultfooterfontstyle = ''; /* blank, B, I, or BI */
        $mpdf->defaultfooterline = 1;  /* 1 to include line below header/above footer */

        $mpdf->SetHeader("||{$this->template->Urad->nazev}");
        // $mpdf->SetFooter("{DATE j.n.Y}/" . $person_name . "||{PAGENO}/{nb}"); /* defines footer for Odd and Even Pages - placed at Outer margin */

        $mpdf->WriteHTML($content);

        $mpdf->Output('sestava.pdf', 'I');
    }

}
