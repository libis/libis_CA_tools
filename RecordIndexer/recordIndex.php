<?php
/**
 * User: NaeemM
 * Date: 9/12/13

 */

if (!_caUtilsLoadSetupPHP()) {
    die("Could not find your CollectiveAccess setup.php file! Please set the COLLECTIVEACCESS_HOME environment variable to the location of your CollectiveAccess installation, or run this command from a sub-directory of your CollectiveAccess installation.\n");
}

$baseDirectory = getenv("COLLECTIVEACCESS_HOME");
$baseDirectory = str_replace("\\",'/', $baseDirectory);
require_once($baseDirectory.'/support/bin/indexUtils.php');

$indexer = new indexUtils();
if(isset($argv[1])){
    switch (strtoupper($argv[1])) {
        case "ATAR":                    //All table all records
            echo "All table all records\n";
            $indexer->indexAllTablesAllRecords();
            break;

        case "OTAR":                    //One table all records
            echo "One table all records\n";
            if(isset($argv[2]))
                $indexer->indexOneTableAllRecords($argv[2]);
            else
                echo "Please provide name of the table in arguments.\n";
            break;

        case "OTOR":                    //One table one records
            echo "One table one records\n";
            if(isset($argv[2]) && isset($argv[3])){
                $ret_value = $indexer->indexOneTableOneRecord($argv[2], $argv[3]);
                echo $ret_value;
            }
            else
                echo "Please provide both name of the table and IDNO in arguments.\n";
            break;

        case "ROI":                     //remove one index
            echo "remove one index\n";
            if(isset($argv[2]) && isset($argv[3])){
                $ret_value = $indexer->removeAnIndex($argv[2], $argv[3]);
                echo $ret_value;
            }
            else
                echo "Please provide both name of the table and IDNO in arguments.\n";
            break;

        case "HELP":                    //help
            echo "\n-----------------help--------------\n";
            echo "\n";
            echo "To index all records of all tables (All Tables All Records):\n";
            echo "\t php recordIndex.php ATAR\n";
            echo "\n";

            echo "To index all records of one table (One Tables All Records):\n";
            echo "\t php recordIndex.php OTAR 'table_name'\n";
            echo "\t\t\t e.g. php recordIndex.php OTAR 'ca_objects'\n";
            echo "\n";

            echo "To index one record of a table (One Tables One Record):\n";
            echo "\t php recordIndex.php OTOR 'table_name' 'IDNO'\n";
            echo "\t\t\t e.g. php recordIndex.php OTAR 'ca_objects' 'CRKC.0080.1276 - (KV_107503)'\n";
            echo "\n";

            echo "To remove an index (Remove One Index):\n";
            echo "\t php recordIndex.php ROI 'table_name' 'IDNO'\n";
            echo "\t\t\t e.g. php recordIndex.php ROI 'ca_objects' 'CRKC.0080.1276 - (KV_107503)'\n";
            echo "\n";

            echo "\n-------------------------------------\n";
            break;
    }
}
else
    echo "Please provide correct arguments. Provide 'help' as an argument for more information. ";



# --------------------------------------------------------
/**
 * Try to locate and load setup.php bootstrap file. If load fails return false and
 * let the caller handle telling the user.
 *
 * @return bool True if setup.php is located and loaded, false if setup.php could not be found.
 */
function _caUtilsLoadSetupPHP() {

    // try to get hostname off of argv since we need that before anything else in a multi-database installation
    if (isset($_SERVER['argv']) && is_array($_SERVER['argv'])) {
        foreach($_SERVER['argv'] as $vs_opt) {
            if (preg_match("!^\-\-hostname\=([A-Za-z0-9_\-\.]+)!", $vs_opt, $va_matches) || preg_match("!^\-h\=([A-Za-z0-9_\-\.]+)!", $vs_opt, $va_matches)) {
                $_SERVER['HTTP_HOST'] = $va_matches[1];
                break;
            }
        }
    }

    // Look for environment variable
    $vs_path = getenv("COLLECTIVEACCESS_HOME");
    if (file_exists("{$vs_path}/setup.php")) {
        require_once("{$vs_path}/setup.php");
        return true;
    }

    // Look in current directory and then in parent directories
    $vs_cwd = getcwd();
    $va_cwd = explode("/", $vs_cwd);
    while(sizeof($va_cwd) > 0) {
        if (file_exists("/".join("/", $va_cwd)."/setup.php")) {
            // Rewrite $_SERVER with paths that setup.php can use
            //print_R($_SERVER);
            // try to load pre-save paths
            if(($vs_hints = @file_get_contents(join("/", $va_cwd)."/app/tmp/server_config_hints.txt")) && is_array($va_hints = unserialize($vs_hints))) {
                $_SERVER['DOCUMENT_ROOT'] = $va_hints['DOCUMENT_ROOT'];
                $_SERVER['SCRIPT_FILENAME'] = $va_hints['SCRIPT_FILENAME'];
                if (!isset($_SERVER['HTTP_HOST'])) { $_SERVER['HTTP_HOST'] = $va_hints['HTTP_HOST']; }
            } else {
                // Guess paths based upon location of setup.php (*should* work)
                if (!isset($_SERVER['DOCUMENT_ROOT']) && !$_SERVER['DOCUMENT_ROOT']) { $_SERVER['DOCUMENT_ROOT'] = join("/", $va_cwd); }
                if (!isset($_SERVER['SCRIPT_FILENAME']) && !$_SERVER['SCRIPT_FILENAME']) { $_SERVER['SCRIPT_FILENAME'] = join("/", $va_cwd)."/index.php"; }
                if (!isset($_SERVER['HTTP_HOST']) && !$_SERVER['HTTP_HOST']) { $_SERVER['HTTP_HOST'] = 'localhost'; }

                print "[\033[1;33mWARNING\033[0m] Guessing base path and hostname because configuration is not available. Loading any CollectiveAccess screen (except for the installer) in a web browser will cache configuration details and resolve this issue.\n\n";
            }

            require_once("/".join("/", $va_cwd)."/setup.php");
            return true;
        }
        array_pop($va_cwd);
    }

    // Give up and die
    return false;
}
# --------------------------------------------------------cu