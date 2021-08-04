<?php
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/lmtools_lib.php";
require_once __DIR__ . "/common.php";

// Currently supported: "flexlm", "mathematica"
class lmtools extends lmtools_lib {

    private const CLI_BINARY  = "%CLI_BINARY%";
    private const CLI_SERVER  = "%CLI_SERVER%";
    private const CLI_FEATURE = "%CLI_FEATURE%";
    private $lm_binaries;
    private $stdout_cache;
    private $fp;
    private $cli;
    private $regex;
    public $err;

    static public function validate_servername($name, $lm) {
        $pattern = lmtools_lib::$_namecheck_regex[$lm]['pattern'];
        $form    = lmtools_lib::$_namecheck_regex[$lm]['form'];

        return array(
            'is_valid' => preg_match($pattern, $name),
            'form'     => $form
        );
    }

    public function __construct() {
        clearstatcache();
        // $this->lm_binaries[license manager] = binary_executable
        // binary_executable is 'path/file' and found in config.php
        foreach (lmtools_lib::LM_SUPPORTED as $supported) {
            global ${$supported['bin']}; // expected to be defined in config.php
            if (isset($supported['lm']) && isset(${$supported['bin']}) && is_executable(${$supported['bin']})) {
                $this->lm_binaries[$supported['lm']] = ${$supported['bin']};
            }
        }

        $this->stdout_cache = "";
        $this->fp    = null;
        $this->cli   = null;
        $this->regex = null;
        $this->err   = null;
    }

    public function __destruct() {
        $this->cli_close();
    }

    public function is_available(string $lm) {
        return isset($this->lm_binaries[$lm]);
    }

    public function list_all_available() {
        $all_lm_available = array_keys($this->lm_binaries);
        sort($all_lm_available, SORT_STRING | SORT_FLAG_CASE);
        return $all_lm_available;
    }

    public function lm_open(string $lm, string $cmd, string $server, string $feature="") {
        switch (false) {
        case $this->lm_check($lm):
        case $this->set_command($cmd, $lm):
            return false;
        }

        $this->stdout_cache = "";
        $binary = $this->lm_binaries[$lm];
        $this->cli = str_replace(self::CLI_BINARY, $binary, $this->cli);
        $this->cli = str_replace(self::CLI_SERVER, $server, $this->cli);
        $this->cli = str_replace(self::CLI_FEATURE, $feature, $this->cli);

        $this->fp = popen($this->cli, "r");

        if ($this->fp === false) {
            $this->cli = null;
            $this->regex = null;
            $this->err = "lmtools.php: Cannot open \"{$cli}\"";
            return false;
        }

        $this->err = null;
        return true;
    }

    public function lm_nextline($patterns=array(0)) {
        if (is_scalar($patterns)) $patterns = array($patterns);
        switch (true) {
        case !is_resource($this->fp) || get_resource_type($this->fp) !== "stream":
            $this->err = "lmtools.php: No license manager is open.";
            return false;
        case is_null($this->cli):
            $this->err = "lmtools.php: LMtool object CLI not set.";
            return false;
        case is_null($this->regex) || count(array_intersect_key(array_flip($patterns), $this->regex)) !== count($patterns):  // ensure all requested regex patterns exist.
            $this->err = "lmtools.php: Unknown regex patterns for current license manager or command.";
            return false;
        }

        $line = fgets($this->fp);
        while (!feof($this->fp)) {
            foreach ($patterns as $pattern) {
                if (preg_match($this->regex[$pattern], $line, $matches) === 1) {
                    $matches = array_filter($matches, function($key) {return is_string($key);}, ARRAY_FILTER_USE_KEY);
                    return array_merge($matches, array('_matched_pattern' => $pattern));
                }
            }

            $line = fgets($this->fp);
        }

        $this->cli_close();
        $this->cli   = null;
        $this->regex = null;
        $this->err   = null;
        return null;
    }

    public function lm_regex_matches(&$regex, &$matches) {
        // TO DO: some error checking
        $this->new_stdout_cache();
        foreach($this->regex as $regex=>$preg) {
            if (preg_match($preg, $this->stdout_cache, $matches) === 1) {
                $matches = array_filter($matches, function($key) {return is_string($key);}, ARRAY_FILTER_USE_KEY);
                return true;
            }
        }

        // No regex matches found in stdout cache
        $regex = null;
        $matches = null;
        return null;
    }

    private function new_stdout_cache() {
        $this->stdout_cache = "";
        while (!feof($this->fp)) $this->stdout_cache .= fgets($this->fp);
    }

    private function lm_check($lm) {
        if (!isset($this->lm_binaries[$lm])) {
            $this->err = "lmtools.php: License Manager \"{$lm}\" not available.";
            return false;
        }

        $this->err = null;
        return true;
    }

    private function cli_close() {
        if (is_resource($this->fp) && get_resource_type($this->fp) === "stream") pclose($this->fp);
    }

    private function set_command($cmd, $lm) {
        try {
            $this->cli   = lmtools_lib::${$cmd}[$lm]['cli'];
            $this->regex = lmtools_lib::${$cmd}[$lm]['regex'];
        } catch (Exception $e) {
            $this->err = "Unknown command or license manager.";
            return false;
        }

        $this->err = null;
        return true;
    }
}
?>
