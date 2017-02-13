<?php

class Test_DefaultPresenter extends BasePresenter
{
    /**
     * @var Nette\Application\IPresenterFactory 
     */
    protected $presenterFactory;
    
    public function __construct(Nette\Application\IPresenterFactory $presenterFactory)
    {
        parent::__construct();
        $this->presenterFactory = $presenterFactory;
    }
        
    protected function isUserAllowed()
    {
        $user = $this->user;
        return $user->isInRole('admin') || $user->isInRole('programator');
    }

    public function renderDefault()
    {
        $link = $this->link("run");
        echo 'Test spustíte <a href="' . $link . '">zde</a>.';
        $this->terminate();
    }

    public function getTests()
    {
        $input = file(__DIR__ . '/tests.txt');

        foreach ($input as $line)
            if ($line{0} != '#') {

                $matches = [];
                preg_match('%^([^ ]+)( ([\w]+))?( ({.*))?%', trim($line), $matches);

                $address = $matches[1];
                $expected = !empty($matches[3]) ? $matches[3] : 'OK';
                $params = isset($matches[5]) ? json_decode($matches[5],
                                true /* vrat pole, ne objekt */) : null;

                $test = array('address' => $address, 'params' => $params, 'expected_result' => $expected);
                $tests[] = $test;
            }

        return $tests;
    }

    public function renderRun()
    {
        // avoid sending extra HTTP headers
        BasePresenter::$testMode = true;
                
        ob_end_clean();

        $this->htmlHeader();
        echo "Spouštím testy\n\n";

        $tests = $this->getTests();
//        dump($tests); die;

        $n_ok = 0;
        foreach ($tests as $test) {

            $result = $this->runTest($test);
            if ($result == $test['expected_result']) {
                $n_ok++;
                if ($result != 'OK')
                    $result = "OK  $result";
            }
            $test_name = $test['address'];
            if ($test['params'])
                $test_name .= " " . json_encode($test['params']);
            printf("%-60s %s\n", $test_name, $result);
            flush();
        }

        if ($n_ok == count($tests))
            echo "\nVšechny testy proběhly úspěšně.";
        else
            printf("\n%d z %d testů bylo úspěšných.", $n_ok, count($tests));

        $this->terminate();
    }

    public function runTest($test)
    {
        try {
            $params = $test['params'] ? : array();
            $presenter = ltrim($test['address'], ":");
            if (substr($presenter, -1) === ':')
                $presenter = rtrim($presenter, ':');
            else {
                $pos = strrpos($presenter, ":");
                $action = substr($presenter, $pos + 1);
                $presenter = substr($presenter, 0, $pos);
                $params['action'] = $action;
            }

            $request = new Nette\Application\Request(
                    $presenter, 'GET', $params);

            $presenter = $this->presenterFactory->createPresenter($request->getPresenterName());
            $presenter->autoCanonicalize = false;

            $response = $presenter->run($request);

            if ($response instanceof Nette\Application\Responses\TextResponse) {

                $urlScript = new Nette\Http\UrlScript();
                $httpRequest = new Nette\Http\Request($urlScript);
                $httpResponse = new Nette\Http\Response();

                ob_start();
                // Je potreba odpoved "odeslat", aby se provedl tez kod sablony a formularu
                $response->send($httpRequest, $httpResponse);
                ob_end_clean();

                return 'OK';
            }

            if ($response === null)
                return 'NULL';
            $classname = get_class($response);
            // Odstran z nazvu namespace
            if (strpos($classname, '\\') !== false)
                $classname = substr($classname, strrpos($classname, '\\') + 1);
            
            return $classname;
        } catch (Exception $e) {
            @ob_end_clean();
            return 'FAIL  ' . get_class($e) . ' - ' . $e->getMessage();
        }
    }
    
    protected function htmlHeader()
    {
        echo <<<EOJ
            <head>
            <meta charset="utf-8">
            <script type='text/javascript'>
            stop_scrolling = false;
            
            function scrollTimer() {
                window.scrollBy(0, 500);
                if (stop_scrolling != true)
                    setTimeout(scrollTimer, 1000);
            }
            
            setTimeout(scrollTimer, 1000);
            </script>
            </head>
            <body onload="stop_scrolling = true">
            <pre>
EOJ;
    }

}
