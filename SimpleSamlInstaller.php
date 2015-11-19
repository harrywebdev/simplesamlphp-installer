<?php

namespace UoN\SimpleSamlInstaller;


use Dotenv;
use Symfony\Component\Filesystem\Filesystem;

class SimpleSamlInstaller
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $env = 'production';

    /**
     *
     */
    public function __construct()
    {
        $this->filesystem = new Filesystem();

        $this->say("\n\n===== SimpleSaml Installer START =====\n");
        $this->setEnvironment();
        $this->setPaths();
    }

    /**
     * Creates new instance of installer and runs it
     * @return mixed
     */
    public static function run()
    {
        return (new static)->install();
    }

    /**
     *
     */
    private function install()
    {
        // check for essentials
        $this->checkEssentials();

        // config templates
        $this->copy('config');

        // metadata templates
        $this->copy('metadata');

        // certificates
        $this->copy('cert');

        // copy modules
        $this->copy('modules');

        // enable modules
        $this->enableModules(['cron', 'metarefresh', 'discopower']);
    }

    /**
     * @param string $what
     */
    private function say($what)
    {
        print $what . "\n";
    }

    /**
     * @param string $what
     */
    private function sayLastWords($what)
    {
        $this->say("\nERROR: " . $what);
        $this->say("Quitting...\n\n");
        exit();
    }

    /**
     * Does some basic checks for folder existence, etc
     */
    private function checkEssentials()
    {
        // check folders
        if (!$this->filesystem->exists(SIMPLESAML_INSTALLER_DATA_DIR)) $this->sayLastWords('Install dir not found: ' . SIMPLESAML_INSTALLER_DATA_DIR);

        $foldersToCheck = array('config', 'metadata', 'modules', 'cert');
        foreach ($foldersToCheck as $folder) {
            if (!$this->filesystem->exists(SIMPLESAML_INSTALLER_DATA_DIR . DIRECTORY_SEPARATOR . $folder)) {
                $this->sayLastWords(sprintf('%s dir not found: %s',
                    ucfirst($folder),
                    SIMPLESAML_INSTALLER_DATA_DIR . DIRECTORY_SEPARATOR . $folder));
            }
        }

        if (!$this->filesystem->exists(SIMPLESAMLPHP_PLUGIN_DIR)) $this->sayLastWords('Simplesamlphp plugin dir not found: ' . SIMPLESAMLPHP_PLUGIN_DIR);
    }

    /**
     * Looks for APP_ENV env variable and sets the environment to that if present
     */
    private function setEnvironment()
    {
        $this->say('Setting environment...');

        try {
            $dotenv = new Dotenv();
            $dotenv->load(realpath(__DIR__ . '/../../../'));
        } catch (\InvalidArgumentException $e) {
            //
        }

        if ($env = getenv('APP_ENV')) {
            $this->env = $env;
        }

        $this->say('Environment: ' . $this->env);
    }

    /**
     * TODO: set path correctly, allow override from arguments (--data_path --simplesamlphp_path ?)
     */
    private function setPaths()
    {
        $this->say('Setting paths...');

        // expects to be in /vendor/uon/simplesamlinstaller
        define('SIMPLESAML_INSTALLER_DATA_DIR', realpath(__DIR__ . '/../../../docs/install/' . $this->env . '/simplesaml/') . DIRECTORY_SEPARATOR);
        define('SIMPLESAMLPHP_PLUGIN_DIR', realpath(__DIR__ . '/../../simplesamlphp/simplesamlphp/') . DIRECTORY_SEPARATOR);
    }

    /**
     * Copies contents of the specified folder
     * @param $folder
     */
    private function copy($folder)
    {
        $this->say('Copying ' . $folder . '...');

        $source = SIMPLESAML_INSTALLER_DATA_DIR . $folder;
        $target = SIMPLESAMLPHP_PLUGIN_DIR . $folder;

        $directoryIterator = new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator          = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                $this->filesystem->mkdir($target . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
                $this->say('  Make dir: ' . $target . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            } else {
                $this->filesystem->copy($item, $target . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
                $this->say('  Copy ' . basename($item) . ' to ' . $target . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            }
        }
    }

    /**
     * Creates 'enable' file in specified module(s) to enable them
     * @param array|string $modules
     */
    private function enableModules($modules)
    {
        if (!is_array($modules)) $modules = array($modules);

        foreach ($modules as $module) {
            if (!$this->filesystem->exists(SIMPLESAMLPHP_PLUGIN_DIR . 'modules' . DIRECTORY_SEPARATOR . $module)) {
                $this->say(sprintf('SKIPPING: %s module not found in: %s',
                    ucfirst($module),
                    SIMPLESAMLPHP_PLUGIN_DIR . 'modules' . DIRECTORY_SEPARATOR . $module));
                continue;
            }

            $this->filesystem->touch(SIMPLESAMLPHP_PLUGIN_DIR . 'modules' . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'enable');
            $this->say('Module "' . $module . '" enabled.');
        }
    }

}