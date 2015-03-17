<?php

abstract class BasePresenter extends Nette\Application\UI\Presenter
{

    public $oldLayoutMode = FALSE;
    public $oldModuleMode = TRUE;

    public function startup()
    {

        if ( !defined('APPLICATION_INSTALL') ):

        $user = Nette\Environment::getUser();
        // $user->setNamespace(KLIENT);

        // Je uzivatel prihlasen?
            if (!$user->isLoggedIn()) {
                if ($user->getLogoutReason() === Nette\Security\User::INACTIVITY) {
                    $this->flashMessage('Uplynula doba neaktivity! Systém vás z bezpečnostních důvodů odhlásil.', 'warning');
                }
                if (!( $this->name == "Spisovka:Uzivatel" && $this->view == "login" )) {
                    // asession ID je pouzito jenom u SSO prihlasovani
                    $asession = $this->getParam('_asession');
                    $alternative = $this->getParam('alternativelogin');
                    $this->forward(':Spisovka:Uzivatel:login', array('_asession'=>$asession, 'alternativelogin'=>$alternative));
                }

            } else {

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

        else:

            if ( strncmp($this->name, 'Install', 7) != 0 ) {
                $this->redirect(':Install:Default:default');
            }

            //echo $this->reflection->name ." - ". $this->getAction();

        endif; // application_install

        parent::startup();
    }

    protected function isUserAllowed()
    {
        return Nette\Environment::getUser()->isAllowed($this->reflection->name, $this->getAction());
    }
    
    protected function beforeRender()
    {
        //$this->template->registerFilter('Nette\Templates\CurlyBracketsFilter::invoke');

        // Helper escapovaný nl2br
        if (!function_exists('enl2br') ) {
            function enl2br($string) {
                return nl2br(htmlspecialchars($string));
            }
        }
        $this->template->registerHelper('enl2br', 'enl2br');
        // Helper escapovaný nl2br + html parser
        if (!function_exists('html2br') ) {
            function html2br($string) {

                if ( strpos($string,"&lt;") !== false ) {
                    $string = html_entity_decode($string);
                }                
                
                $string = preg_replace('#<body.*?>#i', "", $string);
                $string = preg_replace('#<\!doctype.*?>#i', "", $string);
                $string = preg_replace('#</body.*?>#i', "", $string);
                $string = preg_replace('#<html.*?>#i', "", $string);
                $string = preg_replace('#<script.*?>.*?</script>#is', "[javascript blokováno!]", $string);
                $string = preg_replace('#<head.*?>.*?</head>#is', "", $string);
                $string = preg_replace('#<iframe.*?>#i', "[iframe blokováno!]", $string);
                $string = preg_replace('#</iframe>#i', "", $string);
                $string = preg_replace('#src=".*?"#i', "[externí zdroj blokováno!]", $string);
                                
                return nl2br($string);
            }
        }
        $this->template->registerHelper('html2br', 'html2br');
        // Helper vlastni datovy format
        if (!function_exists('edate') ) {
            function edate($string,$format = null) {
                if ( empty($string) ) return "";
                if ( $string == "0000-00-00 00:00:00" ) return "";
                if ( $string == "0000-00-00" ) return "";
                if ( is_numeric($string) ) {
                    return date($format == null ? 'j.n.Y' : $format, $string);
                }
                try {
                    $datetime = new DateTime($string);
                }
                catch (Exception $e) {
                    // datum je neplatné (možná $string vůbec není datum), tak vrať argument
                    return $string;
                }
                
                return $datetime->format($format == null ? 'j.n.Y' : $format);
            }            
        }
        $this->template->registerHelper('edate', 'edate');
        
        if (!function_exists('edatetime') ) {
            function edatetime($string) {
                return edate($string, 'j.n.Y G:i:s');
            }
        }
        $this->template->registerHelper('edatetime', 'edatetime');
        
        if (!function_exists('eyear') ) {
            function eyear($string) {
                if ( empty($string) ) return "";
                if ( $string == "0000-00-00 00:00:00" ) return "";
                if (is_numeric($string) && $string > 1800 && $string < 2200)
                    return $string;
                $datetime = new DateTime($string);
                return $datetime->format('Y');
            }
        }
        $this->template->registerHelper('eyear', 'eyear');
        
        if (!function_exists('num') ) {
            function num($string) {
                return (int) $string;
            }
        }
        $this->template->registerHelper('num', 'num');

        // Nastaveni title
        if ( !isset( $this->template->title ) ) {
            $this->template->title = "";
        }

        // module : presenter : view
        $this->template->view = $this->view;
        $a = strrpos($this->name, ':');
        if ($a === FALSE) {
            $this->template->module = '';
            $this->template->presenter = $this->name;
        } else {
            $this->template->module = substr($this->name, 0, $a + 0);
            $this->template->presenter = substr($this->name, $a + 1);
        }

        if (DEBUG_ENABLE && in_array('programator', Nette\Environment::getUser()->getRoles())) {
            $this->template->debugger = TRUE;
        } else {
            $this->template->debugger = FALSE;
        }

        /**
         *  Servisni mod
         *     - aplikace verejne odstavena
         *     - provadi se udrzba
         */
        /* [P.L.] Tuto funkci aplikace je treba predelat
           - toto je potreba kontrolovat jiz v startup(), tady je prilis pozde
           
           if (file_exists(APP_DIR ."/configs/servicemode") ) {
            $service_mode = 1;    
        } else {
            $service_mode = 0;
        } */

        /**
         * Nastaveni layoutu podle modulu
         */
        if ( defined('APPLICATION_INSTALL') ) {
            $this->setLayout('install');
        } else if ( $this->name == "Spisovka:Uzivatel" && $this->view == "login" ) {
            $this->setLayout('login');
        }
        else switch ($this->template->module) {
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
                
                if ($this->template->presenter == "Napoveda") {
                    $this->setLayout('napoveda');
                    $this->template->module_name = 'Nápověda';
                }
                else if ($this->template->presenter == "Zpravy")
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
        if (Nette\Environment::getHttpRequest()->isAjax())
            $this->setLayout(false);
        
        if (IS_SIMPLE_ROUTER == 1) {
            $helpUri = "?presenter=Spisovka:Napoveda&";
            $helpUri .= strtolower("param1={$this->template->module}&param2={$this->template->presenter}&param3={$this->view}");
        }
        else {
            $helpUri = "napoveda/". strtolower("{$this->template->module}/{$this->template->presenter}/{$this->view}");
        }
        $this->template->helpUri = $helpUri;

        /**
         * Informace o Aplikaci
         */
        $app_info = Nette\Environment::getVariable('app_info');
        if ( !empty($app_info) ) {
            $app_info = explode("#",$app_info);
        } else {
            $app_info = array('3.x','rev.X','OSS Spisová služba v3','1270716764');
        }
        $this->template->AppInfo = $app_info;
        $this->template->KontrolaNovychVerzi = UpdateAgent::je_aplikace_aktualni();

        $this->template->baseUrl = $this->getHttpRequest()->getUrl()->getBasePath();
        $this->template->publicUrl = Nette\Environment::getVariable('publicUrl');
        
        $this->template->licence = '<a href="http://joinup.ec.europa.eu/software/page/eupl/licence-eupl">EUPL v.1.1</a>';
        
        /**
         * Informace o Klientovi
         */
        $user_config = Nette\Environment::getVariable('user_config');
        $this->template->Urad = $user_config->urad;
        
        /**
         * Uzivatel
         */
        $this->template->is_authenticated = false;
        $user = Nette\Environment::getUser();
        if ( $this->name != 'Error' && $user->isLoggedIn() ) {
            $this->template->userobj = $user;
            $identity = $user->getIdentity();
            // var_dump($identity);
            $this->template->user = $identity;
            $this->template->is_authenticated = true;
            
            /**
             * Upozorneni o zpravach uzivateli
            */
            $this->template->zpravy_pocet_neprectenych = 0;
            if ($user->isAllowed('Spisovka_ZpravyPresenter')) {
                // zjisti kolik ma uzivatel neprectenych zprav                
                $this->template->zpravy_pocet_neprectenych = Zpravy::dej_pocet_neprectenych_zprav();
            }
                
        } else {
            $ident = new stdClass();
            $ident->name = "Nepřihlášen";
            $ident->user_roles = array();
            $this->template->user = $ident;
        }

    }
    
    public function templatePrepareFilters($template)
    {
        $latte = new Nette\Latte\Engine;
        $template->registerFilter($latte);
        
        $set = new Nette\Latte\Macros\MacroSet($latte->compiler);
        $set->addMacro('css', 'echo MyMacros::CSS($publicUrl, %node.args);');
        $set->addMacro('js', 'echo MyMacros::JavaScript(%node.word, $publicUrl);');

        $set->addMacro('access', 'if (MyMacros::access(%node.word)) {', '}');
        $set->addMacro('isAllowed', 'if (MyMacros::isAllowed(%node.args)) {', '}');

        $set->addMacro('input2', 'echo MyMacros::input($form, %node.args)');
        
        // Neni momentalne pouzito:
        // $set->addMacro('accessrole', '{', '}');
        
        /* $filter->handler->macros['isAllowed'] =
                '<?php if (%MyMacros::isAllowed%) { ?>';
        $filter->handler->macros['/isAllowed'] =
                '<?php } ?>';
        $filter->handler->macros['accessrole'] =
                '<?php if ( Acl::isInRole("%%")) { ?>';
        $filter->handler->macros['/accessrole'] =
                '<?php } ?>';
                
        $filter->handler->macros['input'] =
            '<?php echo MyMacros::input($form, "%%"); ?>'; */
    }
    
    protected function displayFormErrors(Nette\Forms\Controls\SubmitButton $button)
    {
        $errors = $button->getForm()->getErrors();
        foreach($errors as $error)
            $this->flashMessage($error, 'warning');
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
            "$templates/$dir/$this->view.phtml",
            "$templates/$dir/$this->view.latte",
        );
    }
    
}

