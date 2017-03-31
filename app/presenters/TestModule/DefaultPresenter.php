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
            $params = $test['params'] ?: array();
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

    public function actionCreateDocuments($count)
    {
        if (!$count) {
            $this->flashMessage('Nebyl zadán počet dokumentů.', 'warning');
            $this->redirect('default');
        }

        $data = [
            'dokument_typ_id' => 1,
            'jid' => '12345',
            'nazev' => 'TEST',
            'user_created' => $this->user->id,
            'datum_vzniku' => new \DateTime,
        ];

        $skartacni_znaky = ['A', 'S', 'V'];

        $res = UserAccount::getAll();
        $user_ids = [];
        foreach ($res as $obj) {
            $user_ids[] = $obj->id;
        }
        $user_max = count($user_ids) - 1;

        $res = OrgUnit::getAll();
        $ou_ids = [];
        foreach ($res as $obj) {
            $ou_ids[] = $obj->id;
        }
        $ou_max = count($ou_ids) - 1;

        set_time_limit(0);

        for ($i = 0; $i < $count; $i++) {
            $data['owner_user_id'] = $user_ids[rand(0, $user_max)];
            $data['owner_orgunit_id'] = $ou_ids[rand(0, $ou_max)];
            if (rand(1, 10) == 1) {
                $data['is_forwarded'] = true;
                $data['forward_user_id'] = $user_ids[rand(0, $user_max)];
                $data['forward_orgunit_id'] = $ou_ids[rand(0, $ou_max)];
            } else {
                $data['is_forwarded'] = false;
                $data['forward_user_id'] = null;
                $data['forward_orgunit_id'] = null;
            }

            $data['datum_vzniku'] = new DateTime("@" . rand(1, 1000000000));
            $data['cislo_jednaci'] = "CJ-" . rand(1, 10000);
            $data['skartacni_znak'] = $skartacni_znaky[rand(0, 2)];

            Document::create($data);
        }

        $this->flashMessage("Bylo vytvořeno $count dokumentů.");
        $this->redirect('default');
    }

    public function renderSql()
    {
        $params = $this->context->parameters['database'];
        $mysqli = new mysqli($params['host'], $params['username'], $params['password'],
                $params['database']);
        if ($mysqli->connect_errno)
            throw new Exception('Nepodařilo se připojit do databáze');

        $t1 = microtime(true);
        $sql = 'SELECT SQL_NO_CACHE `d`.`id` 
FROM `dokument` AS `d` 
WHERE (d.owner_user_id = 6 OR d.owner_orgunit_id = 2 OR d.forward_user_id = 6 OR d.forward_orgunit_id = 2)  AND (d.stav < 7 OR
d.stav = 11)
order by id desc
limit 0,200
';

        dump($sql);
        if (!$result = $mysqli->real_query($sql))
            throw new Exception('Provedení dotazu skončilo s chybou');

        $t2 = microtime(true);
        dump(1000 * ($t2 - $t1));

        if ($result = $mysqli->store_result()) {
//            while ($row = $result->fetch_row()) {
//                printf("%s\n", $row[0]);
//            }
            dump($result->num_rows . ' řádek');
            $result->close();
        }

        $t3 = microtime(true);
        dump(1000 * ($t3 - $t2));
        dump(1000 * ($t3 - $t1));

        $mysqli->close();
    }

}
