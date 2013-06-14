<?php

abstract class BasePresenter extends Presenter
{

    public $oldLayoutMode = FALSE;
    public $oldModuleMode = TRUE;

    public function startup()
    {

        if ( !defined('APPLICATION_INSTALL') ):

        $user = Environment::getUser();
        $user->setNamespace(KLIENT);
        // Nema uzivatel pristup na tuto stranku?


        // Je uzivatel prihlasen?
            if (!$user->isAuthenticated()) {
                if ($user->getSignOutReason() === User::INACTIVITY) {
                    $this->flashMessage('Uplynula doba neaktivity! Systém vás z bezpečnostních důvodů odhlásil.', 'warning');
                }
                if (!( $this->name == "Spisovka:Uzivatel" && $this->view == "login" )) {
                    // asession ID je pouzito jenom u SSO prihlasovani
                    $asession = $this->getParam('_asession');
                    $alternative = $this->getParam('alternativelogin');
                    $this->forward(':Spisovka:Uzivatel:login', array('_asession'=>$asession, 'alternativelogin'=>$alternative));
                }

            } else {

                $Acl = Acl::getInstance();

                if ($this->name == "Spisovka:Uzivatel") {
                    // Tento presenter je vzdy pristupny
                    if ($this->view == "login")
                        // Uzivatel je prihlasen - login obrazovka je zbytecna, presmerujeme na uvodni obrazovku
                        // Oprava mozneho bugu s dvojim prihlasovanim = po prihlaseni se opet zobrazuje login obrazovka
                        $this->redirect(':Spisovka:Default:default');
                }
                else if (!$user->isAllowed($this->reflection->name, $this->getAction())) {
                    // Uzivatel je prihlasen, ale nema opravneni zobrazit stranku
                    //$this->forward(':Error:noaccess',array('param'=>array('resource'=>$this->reflection->name,'privilege'=>$this->getAction())));
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
                    if ( !is_null($format) ) {
                        return date($format,$string);
                    } else {
                        return date('j.n.Y',$string);
                    }
                }
                try {
                    $datetime = new DateTime($string);
                }
                catch (Exception $e) {
                    // datum je neplatné (možná $string vůbec není datum), tak vrať argument
                    return $string;
                }
                if ( !is_null($format) ) {
                    return $datetime->format($format);
                } else {
                    return $datetime->format('j.n.Y');
                }                
                return $datetime->format('j.n.Y G:i:s');                
            }
        }
        $this->template->registerHelper('edate', 'edate');
        if (!function_exists('edatetime') ) {
            function edatetime($string) {
                if ( empty($string) ) return "";
                if ( $string == "0000-00-00 00:00:00" ) return "";
                if ( $string == "0000-00-00" ) return "";
                if ( is_numeric($string) ) {
                    return date('j.n.Y G:i:s',$string);
                }
                $datetime = new DateTime($string);
                return $datetime->format('j.n.Y G:i:s');
            }
        }
        $this->template->registerHelper('edatetime', 'edatetime');
        if (!function_exists('eyear') ) {
            function eyear($string) {
                if ( empty($string) ) return "";
                if ( $string == "0000-00-00 00:00:00" ) return "";
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


        //if (DEBUG_ENABLE) {
        if (DEBUG_ENABLE && in_array('programator', Environment::getUser()->getRoles())) {
            $this->template->debuger = TRUE;
        } else {
            $this->template->debuger = FALSE;
        }

        /**
         *  Servisni mod
         *     - aplikace verejne odstavena
         *     - provadi se udrzba
         */
        if (file_exists(APP_DIR ."/configs/servicemode") ) {
            $service_mode = 1;    
        } else {
            $service_mode = 0;
        }


        /**
         * Nastaveni layoutu podle modulu
         */

        if ( $service_mode == 1) {
            $this->setLayout('offline');
        } else if ( defined('APPLICATION_INSTALL') ) {
            $this->setLayout('install');
        } else if ( defined('DB_ERROR') ) {
            $this->setLayout('db');
        } else if ( $this->name == "Spisovka:Uzivatel" && $this->view == "login" ) {
            $this->setLayout('login');
        } else if ( $this->template->module == "Admin" ) {
            if ( $this->getParam("is_ajax") ) {
                $this->setLayout(false);
            } else {
                $this->setLayout('admin');
            }
        } else if ( $this->template->module == "Spisovna" ) {
            if ( $this->getParam("is_ajax") ) {
                $this->setLayout(false);
            } else {
                $this->setLayout('spisovna');
            }
        } else if ( $this->template->module == "Epodatelna" ) {
            if ( $this->getParam("is_ajax") ) {
                $this->setLayout(false);
            } else {
                $this->setLayout('epodatelna');
            }
        }

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
        $app_info = Environment::getVariable('app_info');
        if ( !empty($app_info) ) {
            $app_info = explode("#",$app_info);
        } else {
            $app_info = array('3.x','rev.X','OSS Spisová služba v3','1270716764');
        }
        $this->template->AppInfo = $app_info;
        $this->template->NovaVerze = Zprava::je_aktualni();
        
        $this->template->klientUri = Environment::getVariable('klientUri',Environment::getVariable('baseUri'));
        
        $this->template->licence = '<a href="http://joinup.ec.europa.eu/software/page/eupl/licence-eupl">EUPL v.1.1</a>';
        
        /**
         * Informace o Klientovi
         */
        $user_config = Environment::getVariable('user_config');
        $this->template->Urad = $user_config->urad;
        
        /**
         * Uzivatel
         */
        $user = Environment::getUser();
        if ( $this->name != 'Error' && $user->isAuthenticated() ) {
            $this->template->user = $user->getIdentity();
            $UserModel = new UserModel();
            $this->template->orgjednotka = $UserModel->getOrg($this->template->user);
            
            /**
             * Zobrazeni zprav uzivateli
            */
            if ($user->isAllowed('Spisovka_ZpravyPresenter')) {
                $Zprava = new Zprava();
                $this->template->zpravy = $Zprava->hlasky();            
            }
            else
                $this->template->zpravy = array();
        } else {
            $ident = new stdClass();
            $ident->name = "Nepřihlášen";
            $ident->user_roles = array();
            $this->template->user = $ident;
        }
        

        
        
        
        

    }
    
    public function templatePrepareFilters($template)
    {
        $template->registerFilter($filter = new /*Nette\Templates\*/CurlyBracketsFilter);

        $filter->handler->macros['access'] =
                '<?php if ( @Environment::getUser()->isAllowed(%MyMacros::toParam%) ) { ?>';
        $filter->handler->macros['/access'] =
                '<?php } ?>';
        $filter->handler->macros['accessrole'] =
                '<?php if ( @Acl::isInRole("%%")) { ?>';
        $filter->handler->macros['/accessrole'] =
                '<?php } ?>';
        $filter->handler->macros['accessview'] =
                '<?php if (@$AccessView==1): ?>';
        $filter->handler->macros['/accessview'] =
                '<?php endif; ?>';
        $filter->handler->macros['noaccessview'] =
                '<?php if (!@$AccessView==1): ?>';
        $filter->handler->macros['/noaccessview'] =
                '<?php endif; ?>';

        $filter->handler->macros['accessedit'] =
                '<?php if (@$AccessEdit==1): ?>';
        $filter->handler->macros['/accessedit'] =
                '<?php endif; ?>';


        $filter->handler->macros['vlink'] =
                '<?php echo MyMacros::vlink("%%",%:macroLink%); ?>';
        $filter->handler->macros['alink'] =
                '<?php echo %:macroEscape%(MyMacros::alink("%%")); ?>';
        
    }

}
