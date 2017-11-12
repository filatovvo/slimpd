<?php
namespace Slimpd\Modules\Importer;
/* Copyright (C) 2016 othmar52 <othmar52@users.noreply.github.com>
 *
 * This file is part of sliMpd - a php based mpd web client
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Affero General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class CliController extends \Slimpd\BaseController {
    protected $force = FALSE; // ignore parallel execution (or orphaned .lock file caused by crashed script-run)

    protected $interval = 5; //seconds for recursion of check-que
    protected $maxTime = 59;    // seconds - fits for cronjob executed every minute
    protected $startTime = 0;

    public function __construct($container) {
        $this->container = $container;
        useArguments($this->conf); // required $_SESSION var will be set as soon as we access $this->conf
    }

    public function indexAction(Request $request, Response $response, $args) {
        useArguments($request, $args);
        renderCliHelp($this->ll);
        return $response;
    }

    public function remigrateForceAction(Request $request, Response $response, $args) {
        self::deleteLockFile();
        return $this->remigrateAction($request, $response, $args);
    }

    public function remigrateAction(Request $request, Response $response, $args) {
        useArguments($request, $args);
        if($this->abortOnLockfile($this->ll) === TRUE) {
            return $response;
        }
        self::touchLockFile();
        $importer = new \Slimpd\Modules\Importer\Importer($this->container);
        $importer->triggerImport(TRUE);
        self::deleteLockFile();
        return $response;
    }

    public function remigratealbumAction(Request $request, Response $response, $args) {
        useArguments($request);
        if($this->abortOnLockfile($this->ll) === TRUE) {
            return $response;
        }
        self::touchLockFile();
        $migrator = new \Slimpd\Modules\Importer\Migrator($this->container);
        $args['migrator'] = $migrator->migrateSingleAlbum($args['albumUid']);
        self::deleteLockFile();
        return $response;
    }

    public function updateForceAction(Request $request, Response $response, $args) {
        self::deleteLockFile();
        return $this->updateAction($request, $response, $args);
    }

    public function updateAction(Request $request, Response $response, $args) {
        useArguments($request, $args);
        if($this->abortOnLockfile($this->ll) === TRUE) {
            return $response;
        }
        self::touchLockFile();
        $importer = new \Slimpd\Modules\Importer\Importer($this->container);
        $importer->triggerImport();
        self::deleteLockFile();
        return $response;
    }

    public function builddictsqlAction(Request $request, Response $response, $args) {
        useArguments($request, $args);
        $importer = new \Slimpd\Modules\Importer\DatabaseStuff($this->container);
        $importer->buildDictionarySql();
        return $response;
    }

    public function updateDbSchemeAction(Request $request, Response $response, $args) {
        useArguments($request, $args);
        $action = 'migrate';

        // TODO: manually performed db-changes does not get recognized here - find a solution!

        // check if we can query the revisions table
        $query = "SELECT * FROM db_revisions";
        $result = $this->db->query($query);
        if($result === FALSE) {
            // obviosly table(s) never have been created
            // let's force initial creation of all tables
            $action = 'init';
        }

        \Helper::setConfig( getDatabaseDiffConf($this->conf) );
        if (!\Helper::checkConfigEnough()) {
            \Output::error('mmp: please check configuration');
            return $response;
        }

        # after database-structure changes we have to
        # 1) uncomment next line
        # 2) run ./slimpd update-db-scheme
        # 3) recomment this line again
        # to make a new revision
        #$action = 'create';

        $controller = \Helper::getController($action, NULL);
        if ($controller === false) {
            \Output::error('mmp: unknown command "' . $action . '"');
            \Helper::getController('help')->runStrategy();
            return $response;
        }
        $controller->runStrategy();

        if($action !== 'init') {
            return $response;
        }

        foreach(\Slimpd\Modules\Importer\DatabaseStuff::getInitialDatabaseQueries() as $query) {
            $this->db->query($query);
        }
        return $response;
    }

    public function databaseCleanerAction(Request $request, Response $response, $args) {
        useArguments($request, $args);
        throw new \Exception('TODO: not implemented yet '. __FUNCTION__, 1481874244);

        $importer = new \Slimpd\Modules\Importer\Importer($this->container);
        // phase 11: delete all bitmap-database-entries that does not exist in filesystem
        // TODO: limit this to embedded images only
        $importer->deleteOrphanedBitmapRecords();

        // TODO: delete orphaned artists + genres + labels
        return $response;
    }

    public function hardResetForceAction(Request $request, Response $response, $args) {
        self::deleteLockFile();
        return $this->hardResetAction($request, $response, $args);
    }

    /**
     * start from scratch by dropping and recreating database
     */
    public function hardResetAction(Request $request, Response $response, $args) {
        useArguments($request, $args);
        if($this->abortOnLockfile($this->ll) === TRUE) {
            return $response;
        }
        self::touchLockFile();
        if($this->getDatabaseDropConfirm() === FALSE) {
            self::deleteLockFile();
            return $response;
        }

        try {
            // we cant use $this->db for dropping and creating
            @$db = new \mysqli(
                $this->conf['database']['dbhost'],
                $this->conf['database']['dbusername'],
                $this->conf['database']['dbpassword']
            );
        } catch (\Exception $e) {
            cliLog($this->ll->str('database.connect'), 1, 'red');
            self::deleteLockFile();
            return $response;
        }
        if($db->connect_error) {
            cliLog($this->ll->str('database.connect'), 1, 'red');
            self::deleteLockFile();
            return $response;
        }
        cliLog("Dropping database");

        $result = $db->query("DROP DATABASE IF EXISTS " . $this->conf['database']['dbdatabase'].";");
        cliLog("Recreating database");
        $result = $db->query("CREATE DATABASE " . $this->conf['database']['dbdatabase'].";");
        $action = 'init';

        \Helper::setConfig( getDatabaseDiffConf($this->conf) );
        if (!\Helper::checkConfigEnough()) {
            cliLog("ERROR: invalid mmp configuration", 1, "red");
            self::deleteLockFile();
            return $response;
        }
        // create initial database
        $controller = \Helper::getController($action, NULL);
        $controller->runStrategy();
        // apply all database updates
        $controller = \Helper::getController("migrate", NULL);
        $controller->runStrategy();

        foreach(\Slimpd\Modules\Importer\DatabaseStuff::getInitialDatabaseQueries($this->ll) as $query) {
            $this->db->query($query);
        }

        // delete files created by sliMpd
        $this->filesystemUtility->deleteSlimpdGeneratedFiles();

        $importer = new \Slimpd\Modules\Importer\Importer($this->container);
        $importer->triggerImport();
        self::deleteLockFile();
        return $response;
    }

    protected function getDatabaseDropConfirm() {
        $userInput = '';
        do {
            if ($userInput != "\n") {
                cliLog($this->ll->str("cli.dropdbconfirm", [$this->conf['database']['dbdatabase']]), 1 , "red");
            }
            $userInput = fread(STDIN, 1);
            if (strtolower($userInput) === 'y') {
                return TRUE;
            }
            if (strtolower($userInput) === 'n') {
                cliLog($this->ll->str("cli.dropdbconfirm.abort"));
                return FALSE;
            }
        } while (TRUE);
    }

    /**
     * this function checks if any processing has been requested via sliMpd-GUI
     * TODO: add support for "remigrate" trigger
     */
    public function checkQueAction(Request $request, Response $response, $args) {
        self::heartBeat();
        if($this->startTime === 0) {
            $this->startTime = time();
        }
        cliLog("checking database for importer triggers", 3);
        // check if we have something to process
        $query = "SELECT uid FROM importer
            WHERE batchUid=0 AND jobStart=0 AND jobStatistics='update';";

        if($this->db->query($query)->num_rows === 0) {
            $cliMsg = "Nothing to do. ";

            // check for recursion
            if((time() - $this->startTime) >= $this->maxTime) {
                $cliMsg .= "exiting...";
                cliLog($cliMsg, 1, "green");
                return $response;
            }
            $cliMsg .= "waiting " . $this->interval . " seconds...";
            cliLog($cliMsg, 1, "green");
            sleep($this->interval);
            return $this->checkQueAction($request, $response, $args);
        }

        // avoid parallel execution
        if($this->abortOnLockfile($this->ll) === TRUE) {
            return $response;
        }
        self::touchLockFile();

        cliLog("marking importer triggers as running", 3);
        // update database - so we can avoid parallel execution
        $query = "UPDATE importer
            SET jobStart=". getMicrotimeFloat()."
            WHERE batchUid=0 AND jobStatistics='update';";
        $this->db->query($query);

        // start update process
        $importer = new \Slimpd\Modules\Importer\Importer($this->container);
        $importer->triggerImport();

        // we have reached the maximum waiting time for MPD's update process without starting sliMpd's update process 
        if($importer->keepGuiTrigger === TRUE) {
            cliLog("marking importer triggers as NOT running again", 3);
            $query = "UPDATE importer
                SET jobStart=0
                WHERE batchUid=0 AND jobStatistics='update';";
            $this->db->query($query);
            return $response;
        }

        cliLog("deleting already processed importer triggers from database", 3);
        $query = "DELETE FROM importer
            WHERE batchUid=0 AND jobStatistics='update';";
        $this->db->query($query);

        // TODO: create runSphinxTriggerFile which should be processed by scripts/sphinxrotate.sh

        self::deleteLockFile();
        return $this->checkQueAction($request, $response, $args);
    }

    protected function abortOnLockfile($ll) {
        if(file_exists(APP_ROOT . "localdata/importer.lock") === TRUE) {
            $age = filemtime(APP_ROOT . "localdata/importer.lock");
            cliLog($ll->str("cli.parallel.execution.line1"), 1, "red", TRUE);
            cliLog("  " . $ll->str("cli.parallel.execution.line2",[timeElapsedString($age)]), 1, "yellow");
            cliLog("  " . $ll->str("cli.parallel.execution.line3"), 1, "yellow");
            return TRUE;
        }
        return FALSE;
    }

    public static function touchLockFile() {
        touch(APP_ROOT . "localdata/importer.lock");
        @chmod(APP_ROOT . "localdata/importer.lock", 0777);
    }

    public static function deleteLockFile() {
        cliLog("deleting .lock file", 3);
        @unlink(APP_ROOT . "localdata/importer.lock");
    }

    public static function heartBeat() {
        touch(APP_ROOT . "localdata/importer.heartbeat");
        @chmod(APP_ROOT . "localdata/importer.heartbeat", 0777);
    }

    public static function getHeartBeatTstamp() {
        if(file_exists(APP_ROOT . "localdata/importer.heartbeat") === TRUE) {
            return filemtime(APP_ROOT . "localdata/importer.heartbeat");
        }
        return FALSE;
    }

    /**
     * experimental feature
     * TODO cleanup
     */
    public function destemAction(Request $request, Response $response, $args) {
        useArguments($request);
        if($this->abortOnLockfile($this->ll) === TRUE) {
            return $response;
        }
        self::touchLockFile();

        self::deleteLockFile();
        if(array_key_exists("itemPath", $args) === FALSE) {
            cliLog("ERROR: Missing file argument for destemming", 1 , "red");
            cliLog(" example: ./slimpd destem /music/file.stem.mp4", 1 , "yellow");
            return $response;
        }
        $filePath = $this->filesystemUtility->trimAltMusicDirPrefix($args['itemPath']);
        if($this->filesystemUtility->getFileRealPath($filePath) === FALSE) {
            cliLog("ERROR: invalid filepath", 1 , "red");
            return $response;
        }
        if($this->filesystemUtility->isInAllowedPath($filePath) === FALSE) {
            cliLog("ERROR: outside of allowed filepath", 1 , "red");
            return $response;
        }

        $fileScanner = new \Slimpd\Modules\Importer\Filescanner($this->container);
        $trackInstance = $this->trackRepo->getInstanceByPath($filePath, TRUE);
        if($trackInstance->getFingerPrint()  === NULL) {
            $fingerPrint = $fileScanner->extractAudioFingerprint(
                $this->filesystemUtility->getFileRealPath($filePath)
            );
            $trackInstance->setFingerPrint($fingerPrint);
        }

        #echo "<pre>" . print_r($fingerPrint, 1) . "</pre>";exit;
        $stemDir = APP_ROOT . 'localdata' . DS . 'stems' .
            DS . $trackInstance->getAudioDataFormat() .
            DS . $trackInstance->getFingerPrint() ;


        \phpthumb_functions::EnsureDirectoryExists(
            $stemDir,
            octdec($this->conf['config']['dirCreateMask'])
        );
        file_put_contents($stemDir . DS . "status", "destemming");
        
        $cmd = APP_ROOT . "core/destem-temp.sh " .  escapeshellarg($stemDir) . " " . escapeshellarg($this->filesystemUtility->getFileRealPath($filePath));
        exec($cmd, $result);

        // generate peakfile for all generatred single stem tracks
        $waveformCtrl = new \Slimpd\Modules\WaveformGenerator\Controller($this->container);
        foreach($result as $resultLine) {
            if(preg_match("/^newStemTrack\:\ (.*)$/", $resultLine, $matches)) {
                cliLog("creating peakfile for " . $matches[1], 1);
                $argsSingleStem = [
                    "itemParams" => removeAppRootPrefix($matches[1]),
                    "width" => 10
                ];
                $waveformCtrl->jsonAction($request, $response, $argsSingleStem);
            }
        }
        echo "nix";die;
        print_r($result); die;
        echo $cmd;
        die;
        return $response;
    }
}
