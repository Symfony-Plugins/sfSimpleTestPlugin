<?php

/**
 * @package    symfony
 * @subpackage test
 * @author     slava.hatnuke
 */
class sfSimpleTestRunTask extends sfBaseTask
{

    protected function configure()
    {

        $this->namespace = 'simpletest';
        $this->name = 'run';
        $this->briefDescription = 'Run simpletest testcases';

        $this->detailedDescription = 'The [simpletest:run] Run simpletest testcases:';
    }

    /**
     * @see sfTask
     */
    protected function execute($arguments = array(), $options = array())
    {
        sfSimpleTestManager::execute($arguments = array(), $options = array());
    }
}


/**
 * @package    symfony
 * @subpackage test
 * @author     slava.hatnuke
 */
class sfSimpleTestLoader extends DirectoryIterator
{
    private $manager;

    public function __construct(sfSimpleTestManager $manager, $path)
    {
        parent::__construct($path);
        $this->setManager($manager);
    }

    public function load()
    {
        while ($this->valid())
        {
            if($this->current()->isDir())
            {
                $this->addTestDir();
            }
            else
            {
                $this->addTestCase();
            }
            $this->next();
        }
    }

    public function addTestCase()
    {
        if(preg_match('/\.php$/', $this->getFilename()))
        {
            $this->getManager()->getTestSuite()->addTestFile($this->getPathname());
        }
    }

    public function addTestDir()
    {
        if(!$this->current()->isDot())
        {
            $loader = new self($this->getManager(), $this->getPathname() );
            $loader->load();
        }
    }

    /**
     * @return sfSimpleTestManager
     */
    public function getManager()
    {
        return $this->manager;
    }

    public function setManager(sfSimpleTestManager $manager)
    {
        $this->manager = $manager;
    }
     
}


/**
 * @package    symfony
 * @subpackage test
 * @author     slava.hatnuke
 */
class sfSimpleTestManager
{
    static private $instance;

    public $environment = 'prod';
    
    public $runTitle = "\n\n\n\n\n\n=============== sfSimpleTest Run =============================\n\n";

    private $suite;
    
    private $dbManagerAppName;
        
    /**
    * @return sfSimpleTestManager
    */
    static public function getInstance()
    {
        if(!self::$instance)
        {
            self::$instance= new self;
        }
        return self::$instance;
    }

    static public function initDatabaseManager($appName)
    {
        self::getInstance()->preloadDatabaseManager($appName);
    }            

    public function getConfigPath()
    {
        return $this->getRootPath() . '/config';
    }

    public function getTestCasePath()
    {
        // @TODO move to config
        return $this->getRootPath() . '/test/unit';
    }

    public function getRootPath()
    {
        return sfConfig::get('sf_root_dir');
    }

    public function getPluginPath()
    {
        return realpath( dirname(__FILE__) . '/../../' );
    }

    /**
    * @return TestSuite
    */
    public function getTestSuite()
    {
        if(!$this->suite)
        {
            $this->suite = new TestSuite($this->runTitle);
        }
        return $this->suite;
    }

    public function preloadSimpleTest()
    {
        require_once $this->getConfigPath() . '/ProjectConfiguration.class.php';

        define('SIMPLE_TEST', $this->getPluginPath() . '/lib/simpletest/');

        require_once SIMPLE_TEST . 'unit_tester.php';
        require_once SIMPLE_TEST . 'reporter.php';

    }

    public function preloadDatabaseManager($appName)
    {

        if($this->dbManagerAppName === $appName)return;
        $this->dbManagerAppName = $appName;
        
        $config = new ProjectConfiguration($this->getRootPath());
        
        $appConfig = $config->getApplicationConfiguration(
            $appName, 
            $this->environment, 
            true
        );
        
        sfContext::createInstance($appConfig);
        
        $databaseManager = new sfDatabaseManager( $appConfig );      
        return $databaseManager;  
    }
    
    static public function execute($arguments = array(), $options = array())
    {
        self::getInstance()->run();
    }

    public function run()
    {
        $this->preloadSimpleTest();
        
        $loader = new sfSimpleTestLoader($this, $this->getTestCasePath());
        $loader->load();

        $this->getTestSuite()->run( new TextReporter() );
    }
}