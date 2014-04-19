<?php
/**
 * Install Controller
 *
 * PHP version 5
 *
 * @category Controller
 * @package  Croogo
 * @version  1.0
 * @author   Fahad Ibnay Heylaal <contact@fahad19.com>
 * @license  http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link     http://www.croogo.org
 */

class InstallController extends InstallAppController {

/**
 * Controller name
 *
 * @var string
 * @access public
 */
    public $name = 'Install';
	
	var $scaffoldActions = array('index', 'view', 'add', 'edit');

/**
 * No models required
 *
 * @var array
 * @access public
 */
    public $uses = null;

/**
 * No components required
 *
 * @var array
 * @access public
 */
    public $components = null; //array('Mymodel');
	
	public $templateVars = array();
/**
 * Default configuration
 *
 * @var array
 * @access public
 */
    public $defaultConfig = array(
        'name' => 'default',
        'driver'=> 'mysql',
        'persistent'=> false,
        'host'=> 'localhost',
        'login'=> 'root',
        'password'=> '',
        'database'=> 'croogo',
        'schema'=> null,
        'prefix'=> null,
        'encoding' => 'UTF8',
        'port' => null,
    );
	
	public $newCroogoObjects = array();
	
	public $ignoretables = array(	"acos",
									"aros",
									"aros_acos",
									"blocks",
									"comments",
									"contacts",
									"i18n",
									"languages",
									"links",
									"menus",
									"messages",
									"meta",
									"nodes",
									"nodes_taxonomies",
									"regions",
									"roles",
									"settings",
									"taxonomies",
									"terms",
									"types",
									"types_vocabularies",
									"users",
									"vocabularies");
		
/**
 * beforeFilter
 *
 * @return void
 * @access public
 */
    public function beforeFilter() {
        parent::beforeFilter();

        $this->layout = 'install';
        App::import('Component', 'Session');
        $this->Session = new SessionComponent;
    }
/**
 * If settings.yml exists, app is already installed
 *
 * @return void
 */
    protected function _check() {
        if (file_exists(CONFIGS . 'settings.yml')) {
            $this->Session->setFlash('Already Installed');
            $this->redirect('/');
        }
    }

/**
 * Step 0: welcome
 *
 * A simple welcome message for the installer.
 *
 * @return void
 * @access public
 */
    public function index() {
        $this->_check();
        $this->set('title_for_layout', __('Installation: Welcome', true));
    }

/**
 * Step 1: database
 *
 * Try to connect to the database and give a message if that's not possible so the user can check their
 * credentials or create the missing database
 * Create the database file and insert the submitted details
 *
 * @return void
 * @access public
 */
    public function database() {
        $this->_check();
        $this->set('title_for_layout', __('Step 1: Database', true));

        if (empty($this->data)) {
            return;
		}

        @App::import('Model', 'ConnectionManager');
        $config = $this->defaultConfig;
        foreach ($this->data['Install'] AS $key => $value) {
            if (isset($this->data['Install'][$key])) {
                $config[$key] = $value;
            }
        }
        @ConnectionManager::create('default', $config);
        $db = ConnectionManager::getDataSource('default');
        if (!$db->isConnected()) {
            $this->Session->setFlash(__('Could not connect to database.', true), 'default', array('class' => 'error'));
            return;
        }

        copy(CONFIGS.'database.php.install', CONFIGS.'database.php');
        App::import('Core', 'File');
        $file = new File(CONFIGS.'database.php', true);
        $content = $file->read();

        foreach ($config AS $configKey => $configValue) {
            $content = str_replace('{default_' . $configKey . '}', $configValue, $content);
        }

        if($file->write($content) ) {
            return $this->redirect(array('action' => 'data'));
        } else {
            $this->Session->setFlash(__('Could not write database.php file.', true), 'default', array('class' => 'error'));
        }
    }

/**
 * Step 2: Run the initial sql scripts to create the db and seed it with data
 *
 * @return void
 * @access public
 */
    public function data() {
        $this->_check();
	
        $this->set('title_for_layout', __('Step 2: Build database', true));
        if (isset($this->params['named']['run'])) {
            App::import('Core', 'File');
            App::import('Model', 'CakeSchema', false);
            App::import('Model', 'ConnectionManager');

            $db = ConnectionManager::getDataSource('default');
            if(!$db->isConnected()) {
                $this->Session->setFlash(__('Could not connect to database.', true), 'default', array('class' => 'error'));
            } else {
                $schema =& new CakeSchema(array('name'=>'app'));
                $schema = $schema->load();
                foreach($schema->tables as $table => $fields) {
                    $create = $db->createSchema($schema, $table);
                    $db->execute($create);
                }

		
                $dataObjects = App::objects('class', APP . 'plugins' . DS . 'install' . DS . 'config' . DS . 'data' . DS);
                foreach ($dataObjects as $data) {
                    App::import('class', $data, false, APP . 'plugins' . DS . 'install' . DS . 'config' . DS . 'data' . DS);
                    $classVars = get_class_vars($data);
                    $modelAlias = substr($data, 0, -4);
                    $table = $classVars['table'];
                    $records = $classVars['records'];
                    App::import('Model', 'Model', false);
                    $modelObject =& new Model(array(
                        'name' => $modelAlias,
                        'table' => $table,
                        'ds' => 'default',
                    ));
					
                    if (is_array($records) && count($records) > 0) {
                        foreach($records as $record) {
							//print_r($record);
                            $modelObject->create($record);
                            $modelObject->save();
                        }
                    }
					
                }

                $this->redirect(array('action' => 'buildnewfiles'));
            }
			
        }
		
    }
	
/**
 * Step 3: build MVCs
 *
 * Remind the user to delete 'install' plugin
 * Copy settings.yml file into place
 *
 * @return void
 * @access public
 */
 	function buildnewfiles(){
		
		$this->_check();
		$this->set('title_for_layout', __('Step 3: Build Models, Controllers, Views', true));
		
		App::import('Core', 'File');
		App::import('Model', 'CakeSchema', false);
		App::import('Model', 'ConnectionManager');
		
		$db = ConnectionManager::getDataSource('default');
		$schema =& new CakeSchema(array('name'=>'app'));
		$schema = $schema->load();
	
		App::import('Cake', 'Shell','Console/Command');
		include('../../cake/console/libs/shell.php');
		include('../../cake/console/libs/bake.php');
		include('../../cake/console/libs/console.php');
	
		$assocaitions = array();
		//debugger::dump(get_declared_classes ( ));
		
		foreach($schema->tables as $table => $fields) {
			
			if(!in_array($table,$this->ignoretables)){
				//print_r($this);
				//echo '<br />';
				
				$mymodel = ClassRegistry::Init($table);
				
				$this->bakemodel($mymodel);
				
				//2. build mobile ba file
				$this->bakecontroller($this->_controllerName($mymodel->name),$this->bakeActions($this->_controllerName($mymodel->name)));
				//the controller template was modified to exclude the extenstion from "installAppController"...
				//3. build mobile controller files:
				
				$vars = $this->__loadController($this->_controllerName($mymodel->name));
				$methods = $this->_methodsToBake($this->_controllerName($mymodel->name));
				
				foreach ($methods as $method) {
					
					if(!in_array($method, array('mobileindex','mobileadd','mobilesave','mobiledelete','editindexsavefld','savehabtmfld','deleteall','delete'))){
						
						$content = $this->getContent($method, $vars);
						if ($content) {
							$this->bakeviews($method, $this->_controllerName($mymodel->name), $content);
						}
					}
				}
				
				
		
			}
			
		}
		$this->Session->setFlash(__('Built files!.', true), 'default', array('class' => 'success'));

		
		
		
	}
	
	
	/**
 * Step 3.5: build newMobilefiles
 *
 * Remind the user to delete 'install' plugin
 * Copy settings.yml file into place
 *
 * @return void
 * @access public
 */
 	function buildnewmobilefiles(){
		
		$this->_check();
		$this->set('title_for_layout', __('Step 3: Build Models, Controllers, Views', true));
		
		App::import('Core', 'File');
		App::import('Model', 'CakeSchema', false);
		App::import('Model', 'ConnectionManager');
		
		$db = ConnectionManager::getDataSource('default');
		$schema =& new CakeSchema(array('name'=>'app'));
		$schema = $schema->load();
	
		App::import('Cake', 'Shell','Console/Command');
		include('../../cake/console/libs/shell.php');
		include('../../cake/console/libs/bake.php');
		include('../../cake/console/libs/console.php');
	
		$assocaitions = array();
		
		//build alloy.js!
		$alloystr = '
		Alloy.Globals.RELATIONSHIP = {';
		$alloystr2 = '
		if(Alloy.Globals.LocalDB == true){';
		
		
		foreach($schema->tables as $table => $fields) {
			if(!in_array($table,$this->ignoretables)){
				
				$mymodel = ClassRegistry::Init($table);
				 
				 $alloystr2 .= 'Alloy.Collections.'.ucfirst($mymodel->name).' = Alloy.createCollection("'.$this->_singularName($mymodel->name).'");';
				 
				//2. build mobile model file
				
				$alloystr .= $this->bakeMobileModel($mymodel);
				
				//3. build mobile controller files:
				$this->bakeMobileController($mymodel);
				
				$this->bakeMobileViews($mymodel);
				
			}
			
		}
		
		$alloystr = substr($alloystr, 0, -1);
		$alloystr .= '}';
		
		//echo $alloystr;
		//debugger::dump($alloystr);
		
		$alloystr2 .= '}';
		$this->bakeMobileAlloyFile("Alloy.Globals.BASEURL = 'http://www.derekstearns.com/sampleapp/';".$alloystr, $alloystr2);
		
		$this->Session->setFlash(__('Built files!.', true), 'default', array('class' => 'success'));

		
		
		
	}
	
	

/**
 * Step 4: finish
 *
 * Remind the user to delete 'install' plugin
 * Copy settings.yml file into place
 *
 * @return void
 * @access public
 */
    public function finish() {
        $this->set('title_for_layout', __('Installation completed successfully', true));
        if (isset($this->params['named']['delete'])) {
            App::import('Core', 'Folder');
            $this->folder = new Folder;
            if ($this->folder->delete(APP.'plugins'.DS.'install')) {
                $this->Session->setFlash(__('Installation files deleted successfully.', true), 'default', array('class' => 'success'));
                $this->redirect('/');
            } else {
                return $this->Session->setFlash(__('Could not delete installation files.', true), 'default', array('class' => 'error'));
            }
        }
        $this->_check();

        // set new salt and seed value
        copy(CONFIGS.'settings.yml.install', CONFIGS.'settings.yml');
        $File =& new File(CONFIGS . 'core.php');
        if (!class_exists('Security')) {
            require LIBS . 'security.php';
        }
        $salt = Security::generateAuthKey();
        $seed = mt_rand() . mt_rand();
        $contents = $File->read();
        $contents = preg_replace('/(?<=Configure::write\(\'Security.salt\', \')([^\' ]+)(?=\'\))/', $salt, $contents);
        $contents = preg_replace('/(?<=Configure::write\(\'Security.cipherSeed\', \')(\d+)(?=\'\))/', $seed, $contents);
        if (!$File->write($contents)) {
            return false;
        }

        // set new password for admin, hashed according to new salt value
        $User = ClassRegistry::init('User');
        $User->id = $User->field('id', array('username' => 'admin'));
        $User->saveField('password', Security::hash('password', null, $salt));
    }
	
/**
 * Loads Controller and sets variables for the template
 * Available template variables
 *	'modelClass', 'primaryKey', 'displayField', 'singularVar', 'pluralVar',
 *	'singularHumanName', 'pluralHumanName', 'fields', 'foreignKeys',
 *	'belongsTo', 'hasOne', 'hasMany', 'hasAndBelongsToMany'
 *
 * @return array Returns an variables to be made available to a view template
 * @access private
 */
	function __loadController($controllerName) {
		
		$import = $controllerName;
		if ($this->plugin && $this->plugin != 'install') {
			$import = $this->plugin . '.' . $controllerName;
		}
		
		if (!App::import('Controller', $import)) {
			debugger::dump("The file  could not be found.\nIn order to bake a view, you'll need to first create the controller.");
			$this->_stop();
		}
		$controllerClassName = $controllerName . 'Controller';
		$controllerObj =& new $controllerClassName();
		$controllerObj->plugin = $this->plugin;
		$controllerObj->constructClasses();
		$modelClass = $controllerObj->modelClass;
		$modelObj =& $controllerObj->{$controllerObj->modelClass};

		if ($modelObj) {
			$primaryKey = $modelObj->primaryKey;
			$displayField = $modelObj->displayField;
			$singularVar = Inflector::variable($modelClass);
			$singularHumanName = $this->_singularHumanName($controllerName);
			$schema = $modelObj->schema(true);
			$fields = array_keys($schema);
			$associations = $this->__associations($modelObj);
		} else {
			$primaryKey = $displayField = null;
			$singularVar = Inflector::variable(Inflector::singularize($controllerName));
			$singularHumanName = $this->_singularHumanName($controllerName);
			$fields = $schema = $associations = array();
		}
		$pluralVar = Inflector::variable($controllerName);
		$pluralHumanName = $this->_pluralHumanName($controllerName);

		return compact('modelClass', 'schema', 'primaryKey', 'displayField', 'singularVar', 'pluralVar',
				'singularHumanName', 'pluralHumanName', 'fields','associations');
	}

	
	/**
 * Builds content from template and variables
 *
 * @param string $action name to generate content to
 * @param array $vars passed for use in templates
 * @return string content from template
 * @access public
 */
	function getContent($action, $vars = null) {
		if (!$vars) {
			$vars = $this->__loadController();
		}

		$this->setVars('action', $action);
		$this->setVars('plugin', $this->plugin);
		$this->setVars($vars);
		$template = $this->getTemplate($action);
		if ($template) {
			return $this->generate('views', $template);
		}
		return false;
	}

/**
 * Gets the template name based on the action name
 *
 * @param string $action name
 * @return string template name
 * @access public
 */
	function getTemplate($action) {
		/*if ($action != $this->template && in_array($action, $this->noTemplateActions)) {
			return false;
		}
		if (!empty($this->template) && $action != $this->template) {
			return $this->template;
		} 
		*/
		$template = $action;
		$prefixes = Configure::read('Routing.prefixes');
		foreach ((array)$prefixes as $prefix) {
			if (strpos($template, $prefix) !== false) {
				$template = str_replace($prefix . '_', '', $template);
			}
		}
		if (in_array($template, array('add', 'edit'))) {
			$template = 'form';
		} elseif (preg_match('@(_add|_edit)$@', $template)) {
			$template = str_replace(array('_add', '_edit'), '_form', $template);
		}
		return $template;
	}
	
	/**
 * Get a list of actions that can / should have views baked for them.
 *
 * @return array Array of action names that should be baked (VEIWS!!!!!!!!)
 */
	function _methodsToBake($controllerName) {
		$methods =  array_diff(
			array_map('strtolower', get_class_methods($controllerName . 'Controller')),
			array_map('strtolower', get_class_methods('appcontroller'))
		);
		$scaffoldActions = false;
		if (empty($methods)) {
			$scaffoldActions = true;
			$methods = $this->scaffoldActions;
		}
		$adminRoute = 'admin';//$this->Project->getPrefix();
		foreach ($methods as $i => $method) {
			if ($adminRoute && isset($this->params['admin'])) {
				if ($scaffoldActions) {
					$methods[$i] = $adminRoute . $method;
					continue;
				} elseif (strpos($method, $adminRoute) === false) {
					unset($methods[$i]);
				}
			}
			if ($method[0] === '_' || $method == strtolower($controllerName . 'Controller')) {
				unset($methods[$i]);
			}
		}
		return $methods;
	}
	/**
 * Returns associations for controllers models.
 *
 * @return  array $associations
 * @access private
 */
	function __associations(&$model) {
		$keys = array('belongsTo', 'hasOne', 'hasMany', 'hasAndBelongsToMany');
		$associations = array();

		foreach ($keys as $key => $type) {
			foreach ($model->{$type} as $assocKey => $assocData) {
				$associations[$type][$assocKey]['primaryKey'] = $model->{$assocKey}->primaryKey;
				$associations[$type][$assocKey]['displayField'] = $model->{$assocKey}->displayField;
				$associations[$type][$assocKey]['foreignKey'] = $assocData['foreignKey'];
				$associations[$type][$assocKey]['controller'] = Inflector::pluralize(Inflector::underscore($assocData['className']));
				$associations[$type][$assocKey]['fields'] =  array_keys($model->{$assocKey}->schema(true));
			}
		}
		return $associations;
	}
	
	/**
 * Get shell to use, either plugin shell or application shell
 *
 * All paths in the shellPaths property are searched.
 * shell, shellPath and shellClass properties are taken into account.
 *
 * @param string $plugin Optionally the name of a plugin
 * @return mixed False if no shell could be found or an object on success
 * @access protected
 */
	function _getShell($plugin = null) {
		
		$shell_Paths = array('/Users/VM/Sites/C134/app/vendors/shells/','/Users/VM/Sites/C134/vendors/shells/','/Users/VM/Sites/C134/cake/console/libs/');
		
		foreach ($shell_Paths as $path) {
			$this->shellPath = $path . $this->shell . '.php';
			$pluginShellPath =  DS . $plugin . DS . 'vendors' . DS . 'shells' . DS;
			
			if ((strpos($path, $pluginShellPath) !== false || !$plugin) && file_exists($this->shellPath)) {
				$loaded = true;
				break;
			}
		}
		if (!isset($loaded)) {
			return false;
		}

		if (!class_exists('Shell')) {
			require CONSOLE_LIBS . 'shell.php';
		}

		if (!class_exists($this->shellClass)) {
			require $this->shellPath;
		}
		if (!class_exists($this->shellClass)) {
			return false;
		}
		
		$Shell = new $this->shellClass($this);
		
		return $Shell;
	}
	
	function shiftArgs($args) {
		return array_shift($args);
	}
	
	function _controllerName($name) {
		
		return Inflector::pluralize(Inflector::camelize($name));
	}

/**
 * Creates the proper controller camelized name (singularized) for the specified name
 *
 * @param string $name Name
 * @return string Camelized and singularized controller name
 * @access protected
 */
	function _modelName($name) {
		//debugger::dump($name);
		return Inflector::camelize(Inflector::singularize($name));
	}

/**
 * Creates the proper underscored model key for associations
 *
 * @param string $name Model class name
 * @return string Singular model key
 * @access protected
 */
	function _modelKey($name) {
		return Inflector::underscore($name) . '_id';
	}

/**
 * Creates the proper model name from a foreign key
 *
 * @param string $key Foreign key
 * @return string Model name
 * @access protected
 */
	function _modelNameFromKey($key) {
		return Inflector::camelize(str_replace('_id', '', $key));
	}

/**
 * creates the singular name for use in views.
 *
 * @param string $name
 * @return string $name
 * @access protected
 */
	function _singularName($name) {
		return Inflector::variable(Inflector::singularize($name));
	}

/**
 * Creates the plural name for views
 *
 * @param string $name Name to use
 * @return string Plural name for views
 * @access protected
 */
	function _pluralName($name) {
		return Inflector::variable(Inflector::pluralize($name));
	}

/**
 * Creates the singular human name used in views
 *
 * @param string $name Controller name
 * @return string Singular human name
 * @access protected
 */
	function _singularHumanName($name) {
		return Inflector::humanize(Inflector::underscore(Inflector::singularize($name)));
	}

/**
 * Creates the plural human name used in views
 *
 * @param string $name Controller name
 * @return string Plural human name
 * @access protected
 */
	function _pluralHumanName($name) {
		return Inflector::humanize(Inflector::underscore($name));
	}
	/**
 * Creates the proper controller path for the specified controller class name
 *
 * @param string $name Controller class name
 * @return string Path to controller
 * @access protected
 */
	function _controllerPath($name) {
		return strtolower(Inflector::underscore($name));
	}

	
	/**
 * Assembles and writes a Model file.
 *
 * @param mixed $name Model name or object
 * @param mixed $data if array and $name is not an object assume bake data, otherwise boolean.
 * @access private
 */
	function bakemodel($name, $data = array()) {
		
		if (is_object($name)) {
			
			if ($data == false) {
				
				$data = $associations = array();
				//echo '...baking model '.$name->name.'<br />';
				$data['associations'] = $this->doAssociations($name, $associations);
				//$data['validate'] = $this->doValidation($name);
				
			}
			
			$data['primaryKey'] = $name->primaryKey;
			$data['useTable'] = $name->table;
			$data['useDbConfig'] = $name->useDbConfig;
			$data['name'] = $name = $this->_modelName($name->name);
		} else {
			$data['name'] = $name;
		}
		
		$defaults = array('associations' => array(), 'validate' => array(), 'primaryKey' => 'id',
			'useTable' => null, 'useDbConfig' => 'default', 'displayField' => null);
		$data = array_merge($defaults, $data);
		
		$this->setVars($data);
		$this->setVars('plugin', '');//Inflector::camelize($this->plugin));
		$out = $this->generate('classes', 'model');
        //debugger::dump($out);
		$pathx = rtrim(getcwd (),'webroot').'models/';
		
		
		$path = $pathx;
		$filename = $path . Inflector::underscore($name) . '.php';
		//$this->out("\nBaking model class for $name...");
		$this->createFile($filename, $path, $out);
		ClassRegistry::flush();
		
		return $out;
	}
	
	
	function bakeMobileAlloyFile($alloystr , $alloystr2){
		$totalalloystr = $alloystr . $alloystr2;
		$totalalloystr .= "//:::::::APP CREATOR SYNC OPTIONS:::::::
			//::::THE DB:::::::
			//USER WILL BE ASKED: 'will there be an onboard DB?'
				//IF SO, APPCREATOR will print 'Alloy.Globals.LocalDB = true;'
				//ELSE, APPCREATOR will print 'Alloy.Globals.LocalDB = false;'
				Alloy.Globals.LocalDB = true;
					
				//USER WILL BE ASKED: Install default data on MOBILE INSTALL?
					//IF SO, APPCREATOR will print 'Alloy.Globals.SyncAtInstall = true;'
					//ELSE, APPCREATOR will print 'Alloy.Globals.SyncAtInstall = false;'
					Alloy.Globals.SyncAtInstall = false;
					
				
				//USER WILL BE ASKED: should the app be able to sync automatically?
					//IF SO, APPCREATOR will print 'Alloy.Globals.AutoSync = true;'
					//ELSE, APPCREATOR will print 'Alloy.Globals.AutoSync = false;'
					Alloy.Globals.AutoSync = true;
					
					//USER WILL BE ASKED (if SO), At what frequency?  :: //options: RUNTIME, LOGOUT, LOGIN, TIME
						//AT RUNTIME:
								//APPCREATOR WILL PRINT: 'Alloy.Globals.SyncFreqOpt = 'RUNTIME';'
						//EVERY LOGOUT:
								//APPCREATOR WILL PRINT: 'Alloy.Globals.SyncFreqOpt = 'LOGOUT';'
						//IF EVERY LOGIN: 
								//APPCREATOR WILL PRINT: 'Alloy.Globals.SyncFreqOpt = 'LOGIN';'
						//EVERY # of MINUTES, SECONDS, HOURS, or DAYS:
								//APPCREATOR WILL PRINT: 'Alloy.Globals.SyncFreqOpt = 'TIME';'
								//APPCREATOR WILL PRINT: 'Alloy.Globals.SyncFreqTimeOpt = 'DAYS';'
								//APPCREATOR WILL PRINT: 'Alloy.Globals.SyncFreqTimeVar = '1';'
						Alloy.Globals.SyncFreqOpt = 'RUNTIME';
					
													
					//USER WILL BE ASKED: Do you want to let the Mobile User set the frequency?
						//IF SO, APPCREATOR will print 'Alloy.Globals.AllowDynAutoSync = true;' ......... AND ALL OPTIONS WITH IT!!
						//ELSE, APPCREATOR will print 'Alloy.Globals.AllowDynAutoSync = false;' 
						Alloy.Globals.AllowDynAutoSync = true;
						Alloy.Globals.DefaultSyncFreqOpt = Alloy.Globals.SyncFreqOpt;
												
													
				//APP WILL NOT SYNC AUTOMATICALLY!!!! APPCREATOR WILL PRINT: Alloy.Globals.AutoSync = false; Alloy.Globals.AllowDynSync = true;								
				
				//USER WILL BE ASKED: do you want to let the mobile user sync dynamically (on command)?
					//IF SO, APPCREATOR will print Alloy.Globals.AllowDynSync = true;'
					//ELSE, APPCREATOR will print 'Alloy.Globals.AllowDynSync = false;'
					Alloy.Globals.AllowDynSync = true;
					
			//APP DOES NOT HAVE AN ONBOARD DB!!!!  APPCREATOR WILL PRINT: Alloy.Globals.SyncFreqOpt = 'RUNTIME';	
			
					
			//:::::LOGIN:::::::::		
			//USER WILL BE ASKED: Do you want a login screen?
				//IF SO, APPCREATOR will print 'Alloy.Globals.configureLogin = true;'
				//ELSE, APPCREATOR will print 'Alloy.Globals.configureLogin = false;'
				Alloy.Globals.configureLogin = true;
			
			
			
			
			
			//Testing Globals
			Alloy.Globals.testchildren = 3;
			
			// Android api version
			if( OS_ANDROID ) {
				Alloy.Globals.Android = { 
					'Api' : Ti.Platform.Android.API_LEVEL
				};
			}
			
			// Styles
			Alloy.Globals.Styles = {
				'TableViewRow' : {
					'height' : 45
				}
			};
			
			//Global Post function
			function globalsave(theurl, thedata, modelname, thelocaldata){
				var sendit = Ti.Network.createHTTPClient({
					onerror : function(e) {
						Ti.API.debug(e.error);
						alert(e.error);
					},
					timeout : 1000,
				});
				sendit.onload = function() {
					var json = JSON.parse(this.responseText);
					Ti.API.info(this.responseText);
					if(json.message=='Saved!'){
						//save local data
						thelocaldata['id'] = json.id;
						var myModel = Alloy.createModel(modelname, thelocaldata);
						// save model
						myModel.save();
						// force tables to update
						Alloy.Collections[modelname].fetch();
					   
					}else{
						alert('There was an error in saving the '+modelname+'record.');
					}
					//end new
				};
				sendit.open('POST', theurl);
				sendit.send(thedata);
				
			}
			
			function globalserverdelete(tblname, id){
				var sendit = Ti.Network.createHTTPClient({
					onerror : function(e) {
						Ti.API.debug(e.error);
						//globaldeleterecord( tblname, id);
						alert('There was an error during the connection.  Want to try again?');
					},
					timeout : 1000,
				});
				sendit.open('POST', Alloy.Globals.BASEURL+tblname+'/mobiledelete.json');
				Ti.API.info( Alloy.Globals.BASEURL+tblname+'/mobiledelete');
				//sendit.open('https://maps.googleapis.com/maps/api/place/nearbysearch/json?types=hospital&location=13.01883,80.266113&radius=1000&sensor=false&key=AIzaSyDStAQQtoqnewuLdFwiT-FO0vtkeVx8Sks');
				sendit.send({'id':id});
				// Function to be called upon a successful response
				sendit.onload = function() {
					var json = JSON.parse(this.responseText);
					Ti.API.info(json);
					var db = Titanium.Database.open('_alloy_');
					var rows = db.execute('DELETE FROM '+tblname+' WHERE id = ?',id);
					db.close();
				 };
			};
			
			function globaldelete(e, parentTab, modelname, singlename, dataId, manytomanyaddscreen, tblview){
				if(parentTab!=''){
					tblview.deleteRow(e.index);
					if (typeof Alloy.Globals.RELATIONSHIP[modelname][manytomanyaddscreen] != 'undefined') {
						//HM
						
						globalopenDetail(e, Alloy.Globals.RELATIONSHIP[modelname].sModelname);
					}else{
						
						var db = Titanium.Database.open('_alloy_');
						var mmtblname = Alloy.Globals.RELATIONSHIP[manytomanyaddscreen].related[modelname].manytomanytblname;
						var rows = db.execute('SELECT id FROM '+mmtblname+' WHERE '+Alloy.Globals.RELATIONSHIP[manytomanyaddscreen].singlename+'_id = ? AND '+ singlename + '_id = ?',dataId, e.rowData.dataId);
						Ti.API.info('SELECT id FROM '+mmtblname+' WHERE '+Alloy.Globals.RELATIONSHIP[manytomanyaddscreen].singlename+'_id = '+dataId+' AND '+ singlename + '_id = '+e.rowData.dataId);
						//var rows = db.execute('DELETE FROM '+mmtblname+' WHERE '+singlename+'_id = ? AND '+Alloy.Globals.RELATIONSHIP[manytomanyaddscreen].singlename+'_id = ?',e.rowData.dataId,dataId);
						
						if(rows.getRowCount() == 1){ 
							globalserverdelete(mmtblname, rows.fieldByName('id'));
						}else{
							alert('There is an error in your records. There are '+rows.getRowCount()+' records');
						}
						db.close();
					}	
				}else{
					//delete actual ingredient
					tblview.deleteRow(e.index);
					globalserverdelete( Alloy.Globals.RELATIONSHIP[modelname].tblname, e.rowData.dataId);
				}
			}
			
			function globalgetrecords(modelname, Modelname){
				if (!Ti.App.Properties.hasProperty(modelname+'seeded')) {
					
					var newthing = [];
					var data = [];
					var sendit = Ti.Network.createHTTPClient({
						onerror : function(e) {
							Ti.API.debug(e.error);
							//getrecords();
							alert('There was an error during the connection to get '+modelname+' records');
						},
						timeout : 1000,
					});
					// Here you have to change it for your local ip
					
					sendit.open('POST', Alloy.Globals.BASEURL+modelname+'/mobileindex.json');
					sendit.send({'token':Ti.App.Properties.getString('token')});
					sendit.onload = function() {
						Ti.API.info(this.responseText);
						var json = JSON.parse(this.responseText);
						if (json.length == 0) {
							$.table.headerTitle = 'The database row is empty';
							
						}
						var records = json;
						for ( var i = 0, iLen = records.length; i < iLen; i++) {
							newthing.push(records[i][Modelname]);
						}
						Alloy.Collections[Modelname].reset(newthing);
						Alloy.Collections[Modelname].each(function(_m) {
							_m.save();
						});
						var things = Alloy.Collections[Modelname];
						things.fetch();
						Ti.App.Properties.setString(modelname+'seeded', 'yuppers');
					};
				
				//end if	
				}else{
					//sync
					var has_added = false;
					if(has_added == false){
						//download all!
						
						var newthing = [];
						var data = [];
						var sendit = Ti.Network.createHTTPClient({
							onerror : function(e) {
								Ti.API.debug(e.error);
								//getrecords();
								alert('There was an error during the connection to get '+modelname+' records');
							},
							timeout : 1000,
						});
						// Here you have to change it for your local ip
						
						sendit.open('POST', Alloy.Globals.BASEURL+modelname+'/mobileindex.json');
						sendit.send({'token':Ti.App.Properties.getString('token')});
						sendit.onload = function() {
							Ti.API.info(this.responseText);
							var json = JSON.parse(this.responseText);
							if (json.length == 0) {
								$.table.headerTitle = 'The database row is empty';
								
							}
							var records = json;
							for ( var i = 0, iLen = records.length; i < iLen; i++) {
								newthing.push(records[i][Modelname]);
							}
							Alloy.Collections[Modelname].reset(newthing);
							Alloy.Collections[Modelname].each(function(_m) {
								_m.save();
							});
							var things = Alloy.Collections[Modelname];
							things.fetch();
							Ti.App.Properties.setString(modelname+'seeded', 'yuppers');
						};
					}
				}
				var things = Alloy.Collections[Modelname];
				//fech data
				things.fetch();	
			}
			
			function globalopenChild( e, ManyToManys, ManyToMany, hasmultimanytomany, modelname ){
					if(hasmultimanytomany == true){
						var opts = {
						  cancel: ManyToManys.length-1,
						  options: ManyToManys,
						  title: 'Which Sub Records?'
						};
							
						var dialog = Ti.UI.createOptionDialog(opts);
						
						dialog.addEventListener('click', function(evt)
						{
							//check if cancel
							if(evt.index != ManyToManys.length-1){
								var relationstr = 'related';
								var theController = '';
								var isrelated = false;
								if (ManyToManys[evt.index].indexOf(relationstr) >= 0){
									//HABTM!  Chop string!
									theController = ManyToManys[evt.index].replace('related ', '');
									isrelated = true;
								}else{
									//NOT HABTM
									theController = ManyToManys[evt.index];
								}
								
								var addController = Alloy.createController(theController, {
									parentTab: Alloy.Globals.tabGroup.getActiveTab(),
									dataId: e.rowData.dataId,
									manytomanyaddscreen: modelname,
									related:isrelated
								});
								var addview = addController.getView();
								if (OS_IOS) {
									//Alloy.Globals.navgroup.open(addview); 
									var tab = Alloy.Globals.tabGroup.getActiveTab();
									tab.open(addview);  
								} else if (OS_ANDROID) {
									addview.open();
								}
							  }
								
						});
						
						dialog.show();
						
					}else{
						//only one many to many
						//check if it's a HABTM relation
						var relationstr = 'related';
						var theController = '';
						var isrelated = false;
						if (ManyToMany.indexOf(relationstr) >= 0){
							//HABTM!  Chop string!
							theController = ManyToMany.replace('related ', '');
							isrelated = true;
						}else{
							//NOT HABTM
							theController = ManyToMany;
						}
						var addController = Alloy.createController(theController, {
							parentTab: Alloy.Globals.tabGroup.getActiveTab(),
							dataId: e.rowData.dataId,
							manytomanyaddscreen: modelname,
							related:isrelated
						});
						var addview = addController.getView();
						if (OS_IOS) {
							//Alloy.Globals.navgroup.open(addview); 
							var tab = Alloy.Globals.tabGroup.getActiveTab();
							tab.open(addview);  
						} else if (OS_ANDROID) {
							addview.open();
						}
					}
				};
			
			function globaledittable(e, tblview){
				if(OS_IOS){
					//leave IOS here incase we add Android multidelete functionality
					  if (e.source.title == 'Edit') {
						tblview.editable = false;//deactivate swipe-Delete button
						tblview.editing = true;//Edit:on
						tblview.editing = false;//Edit:off
						tblview.editing = true;//Edit:on again!
						tblview.moving = true;
						e.source.title = 'Done';
					} else { 
						tblview.editable = true;//reactivate swipe-Delete button!
						tblview.editing = false;
						tblview.moving = false;
						e.source.title = 'Edit';
				   }
				
				}
			}
			
			function globalopenAddItem(parentTab, related, modelname, singlename, manytomanyaddscreen, dataId){
				if(parentTab!=''){
					//add new other record
					if(related == true){
						Ti.App.addEventListener('changefield',function(e){ 
							var row = e;
							var db = Titanium.Database.open('_alloy_');
							//find many to many table name with manytomanyaddscreeen name
							var mmtblname = Alloy.Globals.RELATIONSHIP[modelname].related[manytomanyaddscreen]['manytomanytblname'];
							var rows = db.execute('INSERT INTO '+mmtblname+' ('+Alloy.Globals.RELATIONSHIP[manytomanyaddscreen].singlename+'_id, '+singlename+'_id) Values(?,?)', dataId, e.value);
							db.close();
						 });
							var win=Alloy.createController(modelname+'chooser').getView();
							win.open();
					}else{
							var win=Alloy.createController(modelname+'Add').getView();
							win.open();	 
					}
				}else{
						var win=Alloy.createController(modelname+'Add').getView();
						win.open();
				}
			}
			
			function globalopenDetail(_e, Modelname){
					var things = Alloy.Collections[Modelname];
					//Ti.API.info(things.get(_e.rowData.model));
					var addController = Alloy.createController(Modelname+'detail', {
						parentTab: Alloy.Globals.tabGroup.getActiveTab(),
						dataId: _e.rowData.dataId,
						model: things.get(_e.rowData.dataId)
					});
					
					var addview = addController.getView();
					if (OS_IOS) {
						//Alloy.Globals.navgroup.open(addview); 
						var tab = Alloy.Globals.tabGroup.getActiveTab();
						tab.open(addview);  
					} else if (OS_ANDROID) {
						addview.open();
					}
			};";
			
			//create new M2M controller
			$pathx = rtrim(getcwd (),'webroot').'webroot/mobile/app/';
		
			$path = $pathx;
			$filename = $path  . 'alloy.js';
			//$this->out("\nBaking model class for $name...");
			$this->createFile($filename, $path, $totalalloystr);
	
	}
	/**
 * Assembles and writes a Model and returns JS file.
 *
 * @param mixed $name Model name or object
 * @param mixed $data if array and $name is not an object assume bake data, otherwise boolean.
 * @access private
 */	
   function bakeMobileModel($name, $data = array()) {
	  
	   $modelArr =  $array = json_decode(json_encode($name->_schema), true);
	   if (is_object($name)) {
			
			if ($data == false) {
				
				$data = $associations = array();
				
				$data['associations'] = $this->doAssociations($name, $associations);
				//$data['validate'] = $this->doValidation($name);
				
			}
			
			$data['primaryKey'] = $name->primaryKey;
			$data['useTable'] = $name->table;
			$data['useDbConfig'] = $name->useDbConfig;
			$data['name'] = $name = $this->_modelName($name->name);
		} else {
			$data['name'] = $name;
		}
		
		$defaults = array('associations' => array(), 'validate' => array(), 'primaryKey' => 'id',
			'useTable' => null, 'useDbConfig' => 'default', 'displayField' => null);
		$data = array_merge($defaults, $data);
		
		$str = '"'.strtolower($this->_pluralName($name)).'":{
					"Modelname":"'.ucfirst($this->_pluralName($name)).'",
					"modelname":"'.$this->_pluralName($name).'",
					"singlename":"'.$this->_singularName($this->_modelName($name)).'",
					"tblname":"'.$this->_pluralName($name).'",
					"sModelname":"'.$name.'",';
		
		foreach($data['associations']['belongsTo'] as $num => $relname){
			$str .= '"'.$this->_pluralName($relname['alias']).'":{
						"relation":"BT",
						"tblname":"'.$this->_pluralName($relname['alias']).'",
						"Modelname":"'.ucfirst($this->_pluralName($relname['alias'])).'",
						"modelname":"'.$this->_pluralName($relname['alias']).'",
						"sModelname":"'.$relname['alias'].'"
					},';
		}
		//delete last comma
		$str = substr($str, 0, -1);
		foreach($data['associations']['hasMany'] as $num => $relname){
			$str .= '"'.$this->_pluralName($relname['alias']).'":{
						"relation":"HM",
						"tblname":"'.$this->_pluralName($relname['alias']).'",
						"Modelname":"'.ucfirst($this->_pluralName($relname['alias'])).'",
						"modelname":"'.$this->_pluralName($relname['alias']).'",
						"sModelname":"'.$relname['alias'].'"
					},';
		}
		//delete last comma
		$str = substr($str, 0, -1);
		if(!empty($data['associations']['hasAndBelongsToMany'])){
			
			$str .= '"related":{';
			
			foreach($data['associations']['hasAndBelongsToMany'] as $num => $relname){
				$str .= '"'.strtolower($this->_pluralName($relname['alias'])).'":{
							"manytomanytblname":"'.$relname['joinTable'].'",
							"manytomanyModelname":"'.$this->_controllerName($relname['joinTable']).'",
							"manytomanymodelname":"'.strtolower($this->_controllerName($relname['joinTable'])).'"
						},';
			}
			//delete last comma
			$str = substr($str, 0, -1);
			$str .= '}';
		}			
		$str .= '	},';
		
		
		$fldstr = '"id":"INTEGER PRIMARY KEY",';
		
		foreach($modelArr as $key => $vals){
			$fldstr .= '"'.$key.'":"'.$vals['type'].'",'; 
		}
		//check this inflector!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
		$fldstr = substr($fldstr, 0, -1);					
		$out = '// File models/thing.js
					exports.definition = {
						
					  config: {
						
						  columns: {
							  '.$fldstr.'
							 
						  },
						  adapter: {
							  type: "sql",
							  collection_name: "'.Inflector::tableize($name).'",
							  idAttribute: "id"
						  }
						  
						 
					  },        
					  extendModel: function(Model) {        
						  _.extend(Model.prototype, {
							  // extended functions and properties go here
						  });
					 
						  return Model;
					  },
					 
					   extendCollection: function(Collection) {        
						  _.extend(Collection.prototype, {
							  // extended functions and properties go here
						  });
					 
						  return Collection;
					  }
					};';
		
		
		$pathx = rtrim(getcwd (),'webroot').'webroot/mobile/app/models/';
		
		$path = $pathx;
		$filename = $path . Inflector::underscore($name) . '.js';
		//$this->out("\nBaking model class for $name...");
		$this->createFile($filename, $path, $out);
		ClassRegistry::flush();
		return $str;
   }
   
   function bakeMobileController($name){
   
	
	$modelObj = $name;
	$modelArr =  $array = json_decode(json_encode($name->_schema), true);
		if (is_object($name)) {
				
			if ($data == false) {
				
				$data = $associations = array();
				
				$data['associations'] = $this->doAssociations($name, $associations);
				//$data['validate'] = $this->doValidation($name);
				
			}
			
			$data['primaryKey'] = $name->primaryKey;
			$data['useTable'] = $name->table;
			$data['useDbConfig'] = $name->useDbConfig;
			$data['name'] = $name = $this->_modelName($name->name);
		} else {
			$data['name'] = $name;
		}
		//print_r($modelObj->hasAndBelongsToMany);
		$totalrelations = count($data['associations']['belongsTo']) + count($data['associations']['hasMany']) + count($data['associations']['hasAndBelongsToMany']);
		
		foreach($data['associations']['belongsTo'] as $num => $relname){
			$relationstr .= "'".$this->_pluralName($relname['alias'])."',";					
		}
		
		foreach($data['associations']['hasMany'] as $num => $relname){
			$relationstr .= "'".$this->_pluralName($relname['alias'])."',";
		}
		
		foreach($data['associations']['hasAndBelongsToMany'] as $num => $relname){
			$relationstr .= "'related ".$this->_pluralName($relname['alias'])."',";
		}
			
		/*
		////////////////////////////
		create add controller
		///////////////////////////
		*/
		foreach($modelArr as $key => $vals){
			
			//echo 'vals:'.$vals;
			if($key != 'id'){
				$fldstr .= '"'.$key.'":$.'.$key.'.value,';
				$fldstr2 .=  $key.':$.'.$key.'.value,';
				if(strpos($key, '_id') !== false){
					
					$strpos = strpos($key, '_id');
					
					$pickstr .= "$.pick".substr($key, 0, $strpos).".addEventListener('click', function(_e) {
								var win=Alloy.createController('".$this->_pluralName(substr($key, 0, $strpos))."chooser').getView();
								win.open();
								});";
					
					$eventlistenerstr .= "Ti.App.addEventListener('change".substr($key, 0, $strpos)."field',function(e){ 
											$.".$key.".value = e.value;
											$.".substr($key, 0, $strpos).".value = e.title;
										 });";
				}
			}
		}
		
		
		foreach($modelArr as $key => $vals){
			if($key != 'id'){
				
			}
		}
		$fldstr = substr($fldstr, 0, -1);
		$fldstr2 = substr($fldstr2, 0, -1);
		
		$controllerAddStr ="
		var singlename = '".strtolower($name)."';
		var modelname = '".$this->_pluralName($name)."';
		var Modelname = '".$name."';
		var tblname = '".Inflector::tableize($name)."';
		
		var args = arguments[0] || {};
		
		$.savebtn.addEventListener('click', function(_e) {
		
			globalsave( Alloy.Globals.BASEURL+modelname+'/mobileadd/',
			 {
				 ".$fldstr."
			 },
			 Modelname,
			 {		
					".$fldstr2."
			  }
			 );
			
				// close window
				$.AddWindow.close();
		
		});
		//this is for when you have a button that correspsonds to a has many 
		//if has a BT relation add an event listener and a click function for buttons
		".$eventlistenerstr."
		
		".$pickstr."
		
		$.cancelbtn.addEventListener('click', function(e) {
		//var send = things.get(e.rowData.model);
			$.AddWindow.close();
			//Alloy.Globals.testchildren = Alloy.Globals.testchildren - 1;
		});
		";
		//create new M2M controller
		$pathx = rtrim(getcwd (),'webroot').'webroot/mobile/app/controllers/';
	
		$path = $pathx;
		$filename = $path . $this->_pluralName($name) . 'Add.js';
		//$this->out("\nBaking model class for $name...");
		$this->createFile($filename, $path, $controllerAddStr);
		
		
		/*
		////////////////////////////
		create chooser controller
		///////////////////////////
		*/
		$chooseStr =
		"
			var singlename = '".strtolower($name)."';
			var modelname = '".$this->_pluralName($name)."';
			var Modelname = '".$name."';
			var tblname = '".Inflector::tableize($name)."';
			
			
			//We will need to add event listeners for each unique table view created for each tab.
			$.tblview.addEventListener('click', function(e) {
			//var send = things.get(e.rowData.model);
				
				Ti.App.fireEvent('change".strtolower($name)."field', {
					value: e.rowData.dataId,
					title: e.rowData.title
				}); 
				$.tblviewWindow.close();
				
				//Alloy.Globals.testchildren = Alloy.Globals.testchildren - 1;
			});
			if(OS_IOS){
				$.cancel.addEventListener('click', function(e) {
			//var send = things.get(e.rowData.model);
				$.tblviewWindow.close();
				//Alloy.Globals.testchildren = Alloy.Globals.testchildren - 1;
			});
			
			}
			
			var things = Alloy.Collections[Modelname];
			
			things.fetch();	
			 
			function closeWindow(){
				$.tblviewWindow.close();
			} 
			 
			 // Android
			if (OS_ANDROID) {
			   $.tblviewWindow.addEventListener('open', function() {
					if($.tblviewWindow.activity) {
						var activity = $.tblviewWindow.activity;
						// Menu
						//activity.invalidateOptionsMenu();
						activity.onCreateOptionsMenu = function(e) {
							var menu = e.menu;
							if(Alloy.Globals.configureLogin == true){
								//add logout action
								var menuItem1 = menu.add({
									title: L('cancel', 'Cancel'),
									showAsAction: Ti.Android.SHOW_AS_ACTION_ALWAYS
								});
								//add recipe_yingredient!!!!
								menuItem1.addEventListener('click', closeWindow);
							//end logout action
							};
							
							////
							//// add other actions to menu at Home here.
							////
						}; 
					}
				});
				
				// Back Button - not really necessary here - this is the default behaviour anyway?
				$.tblviewWindow.addEventListener('android:back', function() {              
					$.tblviewWindow.close();
					$.tblviewWindow = null;
				});     
			};
			";
			
		$pathx = rtrim(getcwd (),'webroot').'webroot/mobile/app/controllers/';
	
		$path = $pathx;
		$filename = $path . $this->_pluralName($name) . 'chooser.js';
		//$this->out("\nBaking model class for $name...");
		$this->createFile($filename, $path, $chooseStr);
		
		/*
		////////////////////////////
		create detail controller
		///////////////////////////
		*/
		$datatrans = '';
		
		foreach($modelArr as $key => $vals){
			if($key != 'id'){
				$datatrans .= $key.':_model.attributes.'.$key.',';
				$cfldstr2 .=  $key.':$.'.$key.'.value,';
				$cfldstr3 .= 'itemModel.set("'.$key.'", $.'.$key.'.value);
				';
			}
		}
		
		$cfldstr = substr($fldstr, 0, -1);
		$cfldstr2 = substr($fldstr2, 0, -1);
		
		$detailStr =
		"///**************
		/*
		 * 
		 Three variable arrays:
		 Data Transform:
		 Static portion:	id:_model.attributes.id
		 Variable:	 		[fldname]: _model.attributes.[fldname]
		 
		 Save Data:
		 Static Portion:	id: $.name.datid,
		 Variable:			[fldname]: $.[fldname].value,
		 
		 Local Save data:
		 Static portion: NA
		 Variable:		 itemModel.set('[fldname]', $.[fldname].value);
						
		 */
		////*************
		
		var args = arguments[0] || {};
		var parentTab = args.parentTab || '';
		var dataId = (args.dataId === 0 || args.dataId > 0) ? args.dataId : '';
		
		if (dataId !== '') {
			$.thingDetail.set(args.model.attributes);
			
			$.thingDetail = _.extend({}, $.thingDetail, {
				transform : function() {
					return dataTransformation(this);
				}
			});
		
			function dataTransformation(_model) {
				return {
					//ModelVars
					id : _model.attributes.id,
					".$datatrans."
					//ModelVars
				};
			}
		}
		
		function savetoremote(){
			var sendit = Ti.Network.createHTTPClient({
					onerror : function(e) {
						Ti.API.debug(e.error);
						savetoremote();
						alert('There was an error during the connection');
					},
					timeout : 1000,
				});
			sendit.open('GET', Alloy.Glogals.BASEURL+'workers/mobilesave');
			sendit.send({
				//Model Vars
				id: $.name.datid,
				".$cfldstr2."
				//Model Vars
			});
			// Function to be called upon a successful response
			sendit.onload = function() {
				var json = JSON.parse(this.responseText);
				// var json = json.todo;
				// if the database is empty show an alert
				if (json.length == 0) {
					$.table.headerTitle = 'The database row is empty';
					
				}
			};
		}
		
		///Buttons!
		
		$.cancelbtn.addEventListener('click', function(){
			$.".$this->_pluralName($name)."detail.close();
		});
		
		$.savebtn.addEventListener('click', function(){
			var itemModel = args.model;
			//Model VARS
			".$cfldstr3."
			//End model vars
			
			itemModel.save();
			//Alloy.Collections.Thing.fetch();
			savetoremote();
			$.".$this->_pluralName($name)."detail.close();
		});
		
		 // Android
		if (OS_ANDROID) {
			$.".$this->_pluralName($name)."detail.addEventListener('open', function() {
				if($.".$this->_pluralName($name)."detail.activity) {
					var activity = $.".$this->_pluralName($name)."detail.activity;
		
					// Action Bar
					if( Ti.Platform.Android.API_LEVEL >= 11 && activity.actionBar) {      
						activity.actionBar.title = L('detail', 'Detail');
						activity.actionBar.displayHomeAsUp = true; 
						activity.actionBar.onHomeIconItemSelected = function() {               
							$.".$this->_pluralName($name)."detail.close();
							$.".$this->_pluralName($name)."detail = null;
						};             
					}
				}
			});
			
			// Back Button - not really necessary here - this is the default behavior anyway?
			$.".$this->_pluralName($name)."detail.addEventListener('android:back', function() {              
				$.".$this->_pluralName($name)."detail.close();
				$.".$this->_pluralName($name)."detail = null;
			});     
		}";
		
		
		//create new M2M controller
		$pathx = rtrim(getcwd (),'webroot').'webroot/mobile/app/controllers/';
	
		$path = $pathx;
		$filename = $path . $this->_pluralName($name) . 'detail.js';
		//$this->out("\nBaking model class for $name...");
		$this->createFile($filename, $path, $detailStr);
		
		/*
		////////////////////////////
		create Many to Many EDIT controller
		///////////////////////////
		*/
		foreach($data['associations']['hasAndBelongsToMany'] as $num => $relname){
				
			$m2mcontrollerstr .=
			"
			var Modelname = '".strtolower($this->_controllerName($relname['joinTable']))."';
			var modelname = '".strtolower($this->_controllerName($relname['joinTable']))."';
			var tblname = '".$relname['joinTable']."';
			// Check for expected controller args
			//
			var args = arguments[0] || {};
			var parentTab = args.parentTab || '';
			var dataId = (args.dataId === 0 || args.dataId > 0) ? args.dataId : '';
			
			//
			// The list controller shouldn't call detail unless it has an id it is going to pass it in the first place
			// Just double check we got it anyway and do nothing if we didn't
			//
			Ti.API.info(dataId);
			if (dataId != '') {
				//Ti.API.info('id:'+args.dataId.attributes.id);
				$.thingDetail.set(args.model.attributes);
				
				$.thingDetail = _.extend({}, $.thingDetail, {
					transform : function() {
						return dataTransformation(this);
					}
				});
			
				
				function dataTransformation(_model) {
				   // Ti.API.info(_model.attributes.name);
					return {
						id : _model.attributes.id,
						widget_id : _model.attributes.widget_id,
						worker_id : _model.attributes.worker_id,
						itemqty: _model.attributes.numbermade
					};
				}
			}
			
			function savetoremote(){
				var sendit = Ti.Network.createHTTPClient({
						onerror : function(e) {
							Ti.API.debug(e.error);
							savetoremote();
							alert('There was an error during the connection');
						},
						timeout : 1000,
					});
				sendit.open('GET', Alloy.Globals.BASEURL+Modelname+'/mobilesave');
				//sendit.open('https://maps.googleapis.com/maps/api/place/nearbysearch/json?types=hospital&location=13.01883,80.266113&radius=1000&sensor=false&key=AIzaSyDStAQQtoqnewuLdFwiT-FO0vtkeVx8Sks');
				sendit.send({
					id: $.name.datid,
					name: $.name.value,
					description: $.description.value
				});
				// Function to be called upon a successful response
				sendit.onload = function() {
					var json = JSON.parse(this.responseText);
					// var json = json.todo;
					// if the database is empty show an alert
					if (json.length == 0) {
						$.table.headerTitle = 'The database row is empty';
						
					}
				};
			}
			
			$.cancelbtn.addEventListener('click', function(){
				$.".$name."detail.close();
			});
			
			$.savebtn.addEventListener('click', function(){
				var itemModel = args.model;
				//itemModel.set('description', $.description.value);
				itemModel.set('name', $.name.value);
				
				itemModel.save();
			
				// force tables to update
				Alloy.Collections.Thing.fetch();
				//save to remote
				savetoremote();
				$.".$name."detail.close();
			});
			
			 // Android
			if (OS_ANDROID) {
				$.".$name."detail.addEventListener('open', function() {
					if($.".$name."detail.activity) {
						var activity = $.".$name."detail.activity;
			
						// Action Bar
						if( Ti.Platform.Android.API_LEVEL >= 11 && activity.actionBar) {      
							activity.actionBar.title = L('detail', 'Detail');
							activity.actionBar.displayHomeAsUp = true; 
							activity.actionBar.onHomeIconItemSelected = function() {               
								$.".$name."detail.close();
								$.".$name."detail = null;
							};             
						}
					}
				});
				
				// Back Button - not really necessary here - this is the default behaviour anyway?
				$.".$name."detail.addEventListener('android:back', function() {              
					$.".$name."detail.close();
					$.".$name."detail = null;
				});     
			}
			
			// iOS
			// as detail was opened in the tabGroup, iOS will handle the nav itself (back button action and title)
			// but we could change the iOS back button text:
			//$.".$name."detail.backButtonTitle = L('backText', 'Back to List');
			";
			
			//save new M2M controller
			$pathx = rtrim(getcwd (),'webroot').'webroot/mobile/app/controllers/';
		
			$path = $pathx;
			$filename = $path . $relname['joinTable'] . 'Edit.js';
			//$this->out("\nBaking model class for $name...");
			$this->createFile($filename, $path, $m2mcontrollerstr);
		
		}
		
		//delete last comma
		$relationstr = substr($relationstr, 0, -1);
		
		/*
		////////////////////////////
		create main controller FILE
		///////////////////////////
		*/
		$filestr = "
			var singlename = '".$this->_singularName($name)."';
			var modelname = '".$this->_pluralName($name)."';
			var Modelname = '".ucfirst($name)."';
			var tblname = '".$this->_pluralName($name)."';
			";
		
		if($totalrelations > 1){
			$filestr .= "//foreach manytomany get list and add 'cancel'
					var ManyToManys = [".$relationstr.", 'Cancel'];
					var hasmultimanytomany = true;
					//ELSE PRINT
					var ManyToMany = '';//'yingredients';
					//var hasmultimanytomay = false;			
					//Arguments coming in:
					//var hasmultimanytomay = false;
					";
		}else{
			$filestr .="
					//foreach manytomany get list and add 'cancel'
					var ManyToManys = '';//['related widgets', 'Some Other', 'And another','Yet another','Cancel'];
					//var hasmultimanytomany = true;
					//ELSE PRINT
					var ManyToMany = ".$relationstr.";
					var hasmultimanytomany = false;			
					//Arguments coming in:
					//var hasmultimanytomay = false;
					";
		}

		
		$filestr .= 
		"				
			var args = arguments[0] || {};
			var parentTab = args.parentTab || '';
			var manytomanyaddscreen = args.manytomanyaddscreen;
			var related = args.related;
			var dataId = (args.dataId === 0 || args.dataId > 0) ? args.dataId : '';
			//VARS:
			//HAS CHILDREN = true;
			//HAS PARENT = false;
			
			function openAddItem(){
				globalopenAddItem(parentTab, related, modelname, singlename, manytomanyaddscreen, dataId);
			}
			
			function deleterecord(e){
				globaldelete(e, parentTab, modelname, singlename, dataId, manytomanyaddscreen, $.tblview);
				Ti.API.info('e_id:'+e.rowData.dataId);
			}
			
			function editmany(e){
				if(parentTab!=''){
					//check to see the type of relation...if not in relation array, must me related (m2m)
					var checkarray = Alloy.Globals.RELATIONSHIP[tblname];
					var mmtblname = '';
					var mmModelname = '';
					Ti.API.info(checkarray);
					if (typeof Alloy.Globals.RELATIONSHIP[tblname][manytomanyaddscreen] != 'undefined') {
						//HM
					   mmModelname = Alloy.Globals.RELATIONSHIP[tblname].sModelname;
						globalopenDetail(e, mmModelname);
						
					}else{
						//m2m only!
						mmtblname = Alloy.Globals.RELATIONSHIP[tblname].related[manytomanyaddscreen].manytomanytblname;
						mmModelname = Alloy.Globals.RELATIONSHIP[tblname].related[manytomanyaddscreen].manytomanyModelname;
						
							//open recipes_yingredients inspector!!!
						var db = Titanium.Database.open('_alloy_');
						//Ti.API.info(dataId);
						//db.execute('BEGIN IMMEDIATE TRANSACTION');
						var rows = db.execute('SELECT id FROM '+mmtblname+' WHERE '+Alloy.Globals.RELATIONSHIP[manytomanyaddscreen].singlename+'_id = ? AND '+ singlename + '_id = ?',dataId, e.rowData.dataId);
						if(rows.getRowCount() == 1){  
							//Ti.API.info(rows.fieldByName('yingredient_id')); 
							var ythings = Alloy.Collections[mmModelname];
							ythings.fetch();
							//Ti.API.info('id shoulbe: '+rows.fieldByName('id'));
							var addController = Alloy.createController(mmtblname+'Edit', {
								parentTab: Alloy.Globals.tabGroup.getActiveTab(),
								dataId: rows.fieldByName('id'),
								model: ythings.get(rows.fieldByName('id'))			  
							});
							var addview = addController.getView();
							if (OS_IOS) {
								//Alloy.Globals.navgroup.open(addview); 
								var tab = Alloy.Globals.tabGroup.getActiveTab();
								tab.open(addview);  
							} else if (OS_ANDROID) {
								addview.open();
							}
							
							//db.execute('COMMIT TRANSACTION');
							db.close();
						}else{
							alert('Error: you have duplicate records!');
						}
					}
					
				}else{
					globalopenDetail(e, Modelname);
				}
			}
			
			if(OS_IOS){
				$.addbtn.addEventListener('click', function(){
					globalopenAddItem(parentTab, related, modelname, singlename, manytomanyaddscreen, dataId);
				});
				
				$.refresh.addEventListener('click', function(){
					globalgetrecords(modelname, Modelname);
				});
				
				$.editme.addEventListener('click', function(e){
				   globaledittable(e, $.tblview);
				});	
				$.tblview.addEventListener('delete',function(e){
					deleterecord(e);	
					
				});
				$.tblview.addEventListener('longpress', function(e) {
				//var send = things.get(e.rowData.model);
					globalopenDetail(e, Modelname);
				});
				/*
				$.tblview.addEventListener('dblclick', function(e) {
				//var send = things.get(e.rowData.model);
					editmany(e);
				});
				*/
			}
			
			var things = Alloy.Collections[Modelname];
			//fech data
			things.fetch();	
			
			globalgetrecords(modelname, Modelname);
			
			$.tblview.addEventListener('click', function(e) {
				if(parentTab!=''){
					//show detail of related.
					editmany(e);
				}else{
					globalopenChild( e, ManyToManys, ManyToMany, hasmultimanytomany, modelname, parentTab );
			   }
			});
			
			function gettherecords(){
				globalgetrecords(modelname, Modelname);
			}
			
			//loader (both)
			function myLoader(e) {
				// Length before
				var ln = things.models.length;
				Ti.API.info(ln);
				
					var newthing = [];
					var data = [];
					var sendit = Ti.Network.createHTTPClient({
						onerror : function(e) {
							Ti.API.debug(e.error);
							
							alert('There was an error during the connection');
						},
						timeout : 1000,
					});
					// Here you have to change it for your local ip
					var lnstr = (ln/20)+1;
					sendit.open('GET', Alloy.Globals.BASEURL+modelname+'/page:'+lnstr.toString());
					//sendit.open('https://maps.googleapis.com/maps/api/place/nearbysearch/json?types=hospital&location=13.01883,80.266113&radius=1000&sensor=false&key=AIzaSyDStAQQtoqnewuLdFwiT-FO0vtkeVx8Sks');
					sendit.send();
					// Function to be called upon a successful response
					sendit.onload = function() {
						var json = JSON.parse(this.responseText);
						// var json = json.todo;
						// if the database is empty show an alert
						if (json.length == 0) {
							$.table.headerTitle = 'The database row is empty';
							
						}
						// Emptying the data to refresh the view
						// Insert the JSON data to the table view
						var records = json;
						for ( var i = 0, iLen = records.length; i < iLen; i++) {
					
							newthing.push(records[i][Modelname]);
							//Ti.API.info(recipes[i].Recipe.name);
						}
						
						Alloy.Collections[Modelname].reset(newthing);
				
						// save all of the elements
						Alloy.Collections[Modelname].each(function(_m) {
							_m.save();
						});
						
						//Send data to model
						var things = Alloy.Collections[Modelname];
						//fech data
						 things.fetch({
			
							// Some data for the sync adapter to retrieve next page
							data: { offset: ln },
					
							// Don't reset the collection, but add to it
							add: true,
					
							// Don't trigger an add event for every model, but just one fetch
							silent: true,
					
							success: function (col) {
								Ti.API.info('successful here');
								// Call done if we didn't add anymore models
								(col.models.length === ln) ? e.done() : e.success();
							},
					
							error: e.error
						});
						//end new
					   
						
					};
			}
			
			 // Android Navigation
			if(OS_ANDROID) {
				
				$.tblviewWindow.addEventListener('open', function() {
					if($.tblviewWindow.activity) {
						var activity = $.tblviewWindow.activity;
			
						// Action Bar
						if( Ti.Platform.Android.API_LEVEL >= 11 && activity.actionBar) {      
							activity.actionBar.title = L('detail', 'Detail');
							activity.actionBar.displayHomeAsUp = true; 
							activity.actionBar.onHomeIconItemSelected = function() {               
								$.tblviewWindow.close();
								$.tblviewWindow = null;
							};             
						}
						
						activity.onCreateOptionsMenu = function(e) {
							var menu = e.menu;
							 
							// Menu Item 1
							var menuItem1 = menu.add({
								title : 'Add',
								showAsAction : Ti.Android.SHOW_AS_ACTION_NEVER
							});
							
							menuItem1.addEventListener('click', openAddItem);
						};
						
					}
				});
				
				//necessary to change the menu for Add
			 
				$.tblviewWindow.addEventListener('focus', function() {
						if($.tblviewWindow.activity) {
							var activity = Alloy.Globals.tabGroup.activity;
							
							// Menu
							activity.invalidateOptionsMenu();
							activity.onCreateOptionsMenu = function(e) {
								var menu = e.menu;
								if(Alloy.Globals.configureLogin == true){
									//add logout action	
									var menuItem1 = menu.add({
										title: L('add', 'Add '+Modelname),
										showAsAction: Ti.Android.SHOW_AS_ACTION_NEVER
									});
								   menuItem1.addEventListener('click', openAddItem);
									
									var menuItem2 = menu.add({
										title: L('refresh', 'Refresh'),
										showAsAction: Ti.Android.SHOW_AS_ACTION_NEVER
									});
								   
								   menuItem2.addEventListener('click', gettherecords);
									
								   
								};
					
							};            
						}   
					});
				
				  //edit and delete
				$.tblviewWindow.addEventListener('swipe', function(e) {
					if(parentTab!=''){
						var row = e;
						
						var opts = {
						  cancel: 4,
						  options: ['Edit Relation','Delete Relation', 'Edit Record', 'Delete Record', 'Cancel'],
						  title: 'Edit or Delete?'
						};
							
						var dialog = Ti.UI.createOptionDialog(opts);
						 
						dialog.addEventListener('click',function(evt){
							//get relation record
							switch(evt.index){
								case 0:
									editmany(row);
								break;
								case 1:
									deleterecord(row);
								break;
								case 2:
									globalopenDetail(row, Modelname);
								break;
								case 3:
									deleterecord(row);
								break;
							}
						});
						 
						dialog.show();
					
					}else{
						var row = e;
						Ti.API.info('e:'+row);
						var alertDialog = Titanium.UI.createAlertDialog({
							title: 'Edit or Delete Record?',
							message: 'Edit or Delete?',
							buttonNames: ['Edit','Delete', 'Cancel'],
							cancel: 2
						});
						 
						alertDialog.addEventListener('click',function(e){
							switch(e.index){
								case 0:
									 globalopenDetail(row, Modelname);
								break;
								case 1:
									deleterecord(row);
								break;
								case 2:
									//do nothing.
								break;
								
							} 
							   
						});
						 
						alertDialog.show();
					}
					
				  
				});
				
				// Back Button - not really necessary here - this is the default behaviour anyway?
				$.tblviewWindow.addEventListener('android:back', function() {              
					$.tblviewWindow.close();
					$.tblviewWindow = null;
				});     
			};
			$.tblviewWindow.addEventListener('focus', function(e){
				if(parentTab!=''){
					Ti.API.info('args not empty!');
					//var recipesyingredient = Alloy.Collection.RecipesYingredient;
					var things = Alloy.Collections[Modelname];
					//recipesyingredient.fetch({query:'SELECT * FROM recipes_yingredient WHERE recipe_id ='+dataId});
					var db = Titanium.Database.open('_alloy_');
					//db.execute('BEGIN IMMEDIATE TRANSACTION');
					if(related==true){
						//ManyToMany
						var mmtblname = Alloy.Globals.RELATIONSHIP[modelname].related[manytomanyaddscreen].manytomanytblname;
						
						var rows = db.execute('SELECT '+singlename+'_id FROM '+mmtblname+' WHERE '+Alloy.Globals.RELATIONSHIP[manytomanyaddscreen].singlename+'_id = ?',dataId);
						if(rows.rowCount!=0){
							var str = '';
							while(rows.isValidRow()){  
								//Ti.API.info(rows.fieldByName('yingredient_id')); 
								str = str+rows.fieldByName(singlename+'_id')+',';
								rows.next();
							}
							db.close();
							str = str.substring(0, str.length - 1) + ')';
							things.fetch({query:'SELECT * FROM '+tblname+' WHERE id IN ('+str});
						}else{
							
							things.fetch({query:'SELECT * from '+mmtblname+' WHERE id = 0;'});
							$.tblview.headerTitle = 'The database row is empty';
							
						}
					}else{
						//HasMany
						//var rows = db.execute('SELECT * FROM '+hmtblname+' WHERE '+Alloy.Globals.RELATIONSHIP[manytomanyaddscreen].singlename+'_id = ?',dataId);
						things.fetch({query:'SELECT * FROM '+tblname+' WHERE '+Alloy.Globals.RELATIONSHIP[manytomanyaddscreen].singlename+'_id ='+dataId});
					}
				}else{
					Ti.API.info('args empty!');
					//get all records
					var things = Alloy.Collections[Modelname];
					//fech data
					things.fetch();	
				}
			});";
		
			$pathx = rtrim(getcwd (),'webroot').'webroot/mobile/app/controllers/';
			
			$path = $pathx;
			$filename = $path . Inflector::underscore($this->_pluralName($name)) . '.js';
			//$this->out("\nBaking model class for $name...");
			$this->createFile($filename, $path, $filestr);
	   
   }
   
   function bakeMobileViews($name){
		
		$modelObj = $name;
		$modelArr =  $array = json_decode(json_encode($name->_schema), true);
		if (is_object($name)) {
				
			if ($data == false) {
				
				$data = $associations = array();
				
				$data['associations'] = $this->doAssociations($name, $associations);
				//$data['validate'] = $this->doValidation($name);
				
			}
			
			$data['primaryKey'] = $name->primaryKey;
			$data['useTable'] = $name->table;
			$data['useDbConfig'] = $name->useDbConfig;
			$data['name'] = $name = $this->_modelName($name->name);
		} else {
			$data['name'] = $name;
		}
		
		/*
		////////////////////////////
		create main view FILE
		///////////////////////////
		*/
		
		$viewStr = '
		<Alloy>
			<Collection  src="'.$this->_modelName($name).'" />
			<!--<Tab title="Items" icon="KS_nav_ui.png" id="recipestab">-->	
				<Window id="tblviewWindow" ><!--class="container"> -->
					<RightNavButton platform="android,ios">
						<Button id="editme">Edit</Button>
					</RightNavButton>
					<ActivityIndicator id="activityIndicator" />
					<Label id="labelNoRecords" />
					<TableView searchHidden="true" id="tblview" dataCollection="'.$this->_modelName($name).'" moveable="true" editable="true" filterAttribute="title">
					  <SearchBar platform="android,ios"/>
					  <Widget id="is" src="nl.fokkezb.infiniteScroll" onEnd="myLoader" />
					  <TableViewRow id="row" dataId="{id}" title="{name}">
						
						<!--<Label class="rowName" text="{name}"></Label>-->
					  </TableViewRow>
					</TableView>
					 <Toolbar platform="ios" bottom="0" borderTop="true" borderBottom="false">
		
					<!-- The Items tag sets the Toolbar.items property. -->
					<Items>
						
					   
						<Button id="camera" systemButton="Ti.UI.iPhone.SystemButton.CAMERA" />
						<FlexSpace/>
						<Button id="refresh" systemButton="Titanium.UI.iPhone.SystemButton.REFRESH" />
						<FlexSpace/>
						<Button id="addbtn" systemButton="Titanium.UI.iPhone.SystemButton.ADD" />
					</Items>
		
					<!-- Place additional views for the Toolbar here. -->
		
					</Toolbar>
				</Window>
				
			<!--</Tab>-->
			<!--code will insert new tabs here????-->
		</Alloy>
		';
		
		$pathx = rtrim(getcwd (),'webroot').'webroot/mobile/app/views/';
			
		$path = $pathx;
		$filename = $path . $this->_pluralName($name) . '.xml';
		//$this->out("\nBaking model class for $name...");
		$this->createFile($filename, $path, $viewStr);
		
		/*
		////////////////////////////
		create detail view FILE
		///////////////////////////
		*/
		$cfldstrdetail = '';
		foreach($modelArr as $key => $vals){
			if($key != 'id' && $key != 'name'){
				$cfldstrdetail .= '<TextField id="'.$key.'" value="{$.thingDetail.'.$key.'}" ></TextField>';
				
			}
		}
		
		$detailStrView = 
		'<Alloy>
			<Model src="'.$this->_modelName($name).'" instance="true" id="thingDetail">
			<Window id="detail" model="$.thingDetail" dataTransform="dataTransformation" layout="vertical"> 
				<TextField id="name" datid = "{$.thingDetail.id}" value="{$.thingDetail.name}"  hintText="Name" ></TextField>
				'.$cfldstrdetail.'
				<Button id="savebtn">Save</Button>
				<Button id="cancelbtn">Cancel</Button>
			</Window>
		</Alloy>
		';
		
		$pathx = rtrim(getcwd (),'webroot').'webroot/mobile/app/views/';
			
		$path = $pathx;
		$filename = $path . $this->_pluralName($name) . 'detail.xml';
		//$this->out("\nBaking model class for $name...");
		$this->createFile($filename, $path, $detailStrView);
		
		
		/*
		////////////////////////////
		create chooser view FILE
		///////////////////////////
		*/
		$chooserStr =
		'<Alloy>
			<Collection  src="'.$this->_modelName($name).'" />
			
				<Window id="tblviewWindow" ><!--class="container"> -->
					
					<ActivityIndicator id="activityIndicator" />
					<Label id="labelNoRecords" />
					<TableView id="tblview" dataCollection="'.$this->_modelName($name).'" editable="true" filterAttribute="title">
					  <SearchBar platform="android,ios"/>
					 
					  <TableViewRow id="row" dataId="{id}" model="{alloy_id}" title="{name}">
						<!--<Label class="rowName" text="{name}"></Label>-->
					  </TableViewRow>
					</TableView>
					<Toolbar platform="ios" bottom="0" borderTop="true" borderBottom="false">
					<!-- The Items tag sets the Toolbar.items property. -->
					<Items>
						<FlexSpace/>
						<Button id="cancel" systemButton="Titanium.UI.iPhone.SystemButton.CANCEL" />
						<FlexSpace/>
					</Items>
				</Toolbar>
				</Window>
			
			<!--code will insert new tabs here????-->
		</Alloy>
		';
		
		
		$pathx = rtrim(getcwd (),'webroot').'webroot/mobile/app/views/';
			
		$path = $pathx;
		$filename = $path . $this->_pluralName($name) . 'chooser.xml';
		//$this->out("\nBaking model class for $name...");
		$this->createFile($filename, $path, $chooserStr);
		
		/*
		////////////////////////////
		create Many to ManyEdit view FILE
		///////////////////////////
		*/
		if(strpos(Inflector::tableize($name), '_') !== false){
			foreach($modelArr as $key => $vals){
					if($key != 'id'){
						$cfldstr4 .= '<TextField id="'.$key.'" value="{$.thingDetail.'.$key.'}" ></TextField>';
					}
				}
				
		//TODO Check MODEL SRC for join tables
			$m2mEditStr = 
			'<Alloy>
			
				<Model src="'.Inflector::tableize($name).'" instance="true" id="thingDetail">
				<Window id="detail" model="$.thingDetail" dataTransform="dataTransformation" layout="vertical"> 
					<!-- <TextField id="widget_id" datid = "{$.thingDetail.id}" value="{$.thingDetail.widget_id}"></TextField>-->
				   
					'.$cfldstr4.'
					<Button id="savebtn">Save</Button>
					<Button id="cancelbtn">Cancel</Button>
				</Window>
			</Alloy>';
			
			
			$pathx = rtrim(getcwd (),'webroot').'webroot/mobile/app/views/';
				
			$path = $pathx;
			$filename = $path . Inflector::tableize($name) . 'Edit.xml';
			//$this->out("\nBaking model class for $name...");
			$this->createFile($filename, $path, $m2mEditStr);
			
		}
		
		/*
		////////////////////////////
		create add view FILE
		///////////////////////////
		*/
		///$addviewstr ='';
		//print_r($modelArr);
		foreach($modelArr as $key => $vals){
			
			//echo 'vals:'.$vals;
			if($key != 'id'){
				if(strpos($key, '_id') !== false){
					
					$strpos = strpos($key, '_id');
					$addviewstr .= '<TextField id="'.$key.'" value="{$.thingDetail.'.$key.'}" ></TextField>
					';
				//create extra feild (to show name of linked model
					$addviewstr .= '<TextField id="'.substr($key, 0, $strpos).'name"></TextField>';
				//create extra button!
					$addviewstr .= '<Button id="pick'.substr($key, 0, $strpos).'">'.substr($key, 0, $strpos).'</Button>';
				}else{
					//echo 'key:'.$key;
					$addviewstr .= '<TextField id="'.$key.'" hintText="'.$key.'" value="{$.thingDetail.'.$key.'}" ></TextField>
					';
				}
			}
		}
		
		//echo 'cfld:'.$cfldstr2;
		
		$addStr = '
		<Alloy>
			<Model src="'.$this->_modelName($name).'" instance="true" id="thingDetail">
			<Window id="AddWindow">
				
				<ScrollView id="addView" layout="vertical" >'.
				$addviewstr.'
					
					<Button id="savebtn" title="Save"></Button>
					<Button id="cancelbtn" title="Cancel"></Button>
				</ScrollView>
			</Window>
		</Alloy>
		';
		//echo $addStr;
		$pathx = rtrim(getcwd (),'webroot').'webroot/mobile/app/views/';
			
		$path = $pathx;
		$filename = $path . $this->_pluralName($name) . 'Add.xml';
		//$this->out("\nBaking model class for $name...");
		$this->createFile($filename, $path, $addStr);
		
   
   }
	
/**
 * Bake scaffold actions
 *
 * @param string $controllerName Controller name
 * @param string $admin Admin route to use
 * @param boolean $wannaUseSession Set to true to use sessions, false otherwise
 * @return string Baked actions
 * @access private
 */
	function bakeActions($controllerName, $admin = null, $wannaUseSession = true) {
		
		$currentModelName = $modelImport = $this->_modelName($controllerName);
		$plugin = $this->plugin;
		//debugger::dump($currentModelName);
		if ($plugin && $plugin != 'install') {
			$modelImport = $plugin . '.' . $modelImport;
		}
		/*
		if (!App::import('Model', $modelImport)) {
			$this->err(__('You must have a model for this class to build basic methods. Please try again.', true));
			$this->_stop();
		}
	*/
		$modelObj =& ClassRegistry::init($currentModelName);
		$controllerPath = $this->_controllerPath($controllerName);
		$pluralName = $this->_pluralName($currentModelName);
		$singularName = Inflector::variable($currentModelName);
		$singularHumanName = $this->_singularHumanName($controllerName);
		$pluralHumanName = $this->_pluralName($controllerName);

		$this->setVars(compact('plugin', 'admin', 'controllerPath', 'pluralName', 'singularName', 'singularHumanName',
			'pluralHumanName', 'modelObj', 'wannaUseSession', 'currentModelName'));
		$actions = $this->generate('actions', 'controller_actions');
		return $actions;
	}
	

	/**
 * Assembles and writes a Controller file
 *
 * @param string $controllerName Controller name
 * @param string $actions Actions to add, or set the whole controller to use $scaffold (set $actions to 'scaffold')
 * @param array $helpers Helpers to use in controller
 * @param array $components Components to use in controller
 * @param array $uses Models to use in controller
 * @return string Baked controller
 * @access private
 */
	function bakecontroller($controllerName, $actions = '', $helpers = null, $components = null) {
		//debugger::dump($controllerName);
		$isScaffold = ($actions === 'scaffold') ? true : false;
		
		if($this->plugin != 'install'){
			$this->setVars('plugin', Inflector::camelize($this->plugin));
		}
		$this->setVars(compact('controllerName', 'actions', 'helpers', 'components', 'isScaffold'));
		$contents = $this->generate('classes', 'controller');

		$path = rtrim(getcwd (),'webroot').'controllers/';
	
		$filename = $path . $this->_controllerPath($controllerName) . '_controller.php';
		
		if ($this->createFile($filename, $path, $contents)) {
			
			return $contents;
		}
		return false;
	}
		/**
 * Assembles and writes bakes the view file.
 *
 * @param string $action Action to bake
 * @param string $content Content to write
 * @return boolean Success
 * @access public
 */
	function bakeviews($action, $controller, $content = '') {
		if ($content === true) {
			$content = $this->getContent($action);
		}
		if (empty($content)) {
			return false;
		}
		$path = rtrim(getcwd (),'webroot').'views/';
		//debugger::dump($content);
		//$path = $this->getPath();
		$filename = $path . lcfirst($controller) . DS . Inflector::underscore($action) . '.ctp';
		return $this->createFile($filename, $path, $content);
	}
	
	/**
 * Handles associations
 *
 * @param object $model
 * @return array $assocaitons
 * @access public
 */
	function doAssociations(&$model) {
		if (!is_object($model)) {
		
			return false;
		}

		$fields = $model->schema(true);
		if (empty($fields)) {
			
			return false;
		}

		if (empty($this->_tables)) {
			$this->_tables = $this->getAllTables();
		}

		$associations = array(
			'belongsTo' => array(), 'hasMany' => array(), 'hasOne'=> array(), 'hasAndBelongsToMany' => array()
		);
		$possibleKeys = array();

		$associations = $this->findBelongsTo($model, $associations);
		
		$associations = $this->findHasOneAndMany($model, $associations);
	
		$associations = $this->findHasAndBelongsToMany($model, $associations);
		
		return $associations;
	}

/**
 * Find belongsTo relations and add them to the associations list.
 *
 * @param object $model Model instance of model being generated.
 * @param array $associations Array of inprogress associations
 * @return array $associations with belongsTo added in.
 */
	function findBelongsTo(&$model, $associations) {
		$fields = $model->schema(true);
		foreach ($fields as $fieldName => $field) {
			$offset = strpos($fieldName, '_id');
			if ($fieldName != $model->primaryKey && $fieldName != 'parent_id' && $offset !== false) {
				$tmpModelName = $this->_modelNameFromKey($fieldName);
				$associations['belongsTo'][] = array(
					'alias' => $tmpModelName,
					'className' => $tmpModelName,
					'foreignKey' => $fieldName,
				);
			} elseif ($fieldName == 'parent_id') {
				$associations['belongsTo'][] = array(
					'alias' => 'Parent' . $model->name,
					'className' => $model->name,
					'foreignKey' => $fieldName,
				);
			}
		}
		return $associations;
	}

/**
 * Find the hasOne and HasMany relations and add them to associations list
 *
 * @param object $model Model instance being generated
 * @param array $associations Array of inprogress associations
 * @return array $associations with hasOne and hasMany added in.
 */
	function findHasOneAndMany(&$model, $associations) {
		
		$foreignKey = $this->_modelKey($model->name);
		//echo 'finding One and Many...<br />';
		//debugger::dump($this->_tables->tables);
		foreach ($this->_tables->tables as $mname => $otherTable) {
			
				//debugger::dump($mname);
				//echo 'try getting table for '.$mname.'<br />';
				$tempOtherModel = $this->_getModelObject($this->_modelName($mname), $mname);
				//echo 'got table for '.$mname.'<br />';
				$modelFieldsTemp = $tempOtherModel->schema(true);
				$pattern = '/_' . preg_quote($model->table, '/') . '|' . preg_quote($model->table, '/') . '_/';
				//debugger::dump($pattern);
				//echo 'pattern for tbl - '.$mname.':'.$pattern.'<br />';
				$possibleJoinTable = preg_match($pattern , $mname);
				if ($possibleJoinTable == true) {
					continue;
					
				}
				//echo 'foreach <br />';
				foreach ($modelFieldsTemp as $fieldName => $field) {
					//echo 'fldname:'.$fieldName.'<br />';
					$assoc = false;
					if ($fieldName != $model->primaryKey && $fieldName == $foreignKey) {
						$assoc = array(
							'alias' => $tempOtherModel->name,
							'className' => $tempOtherModel->name,
							'foreignKey' => $fieldName
						);
						
					} elseif ($otherTable == $model->table && $fieldName == 'parent_id') {
						$assoc = array(
							'alias' => 'Child' . $model->name,
							'className' => $model->name,
							'foreignKey' => $fieldName
						);
						
					}
					
					if ($assoc) {
						
						$associations['hasOne'][] = $assoc;
						$associations['hasMany'][] = $assoc;
					}
					
				}
				//print_r($associations);
				//echo '<br />';
				//echo '<br />';
		}
		
		return $associations;
	}

/**
 * Find the hasAndBelongsToMany relations and add them to associations list
 *
 * @param object $model Model instance being generated
 * @param array $associations Array of inprogress associations
 * @return array $associations with hasAndBelongsToMany added in.
 */
	function findHasAndBelongsToMany(&$model, $associations) {
		
		$foreignKey = $this->_modelKey( $this->_singularName($model->name ));
		//debugger::dump($model->name);
		foreach ($this->_tables->tables as $tname => $otherTable) {
			
			$tempOtherModel = $this->_getModelObject($this->_modelName($tname), $tname);
			$modelFieldsTemp = $tempOtherModel->schema(true);
			$offset = strpos($tname, $model->table . '_');
			$otherOffset = strpos($tname, '_' . $model->table);

			if ($offset !== false) {
				
				$offset = strlen($model->table . '_');
			
				$habtmName = $this->_modelName(substr($tname, $offset));
				
				$associations['hasAndBelongsToMany'][] = array(
					'alias' => $habtmName,
					'className' => $habtmName,
					'foreignKey' => $foreignKey,
					'associationForeignKey' => $this->_modelKey($habtmName),
					//'joinTable' => $otherTable
					'joinTable'=>$tname
				);
			} elseif ($otherOffset !== false) {
				
				$habtmName = $this->_modelName(substr($tname, 0, $otherOffset));
				$associations['hasAndBelongsToMany'][] = array(
					'alias' => $habtmName,
					'className' => $habtmName,
					'foreignKey' => $foreignKey,
					'associationForeignKey' => $this->_modelKey($habtmName),
					//'joinTable' => $otherTable
					'joinTable'=>$tname
				);
			}
		}
		return $associations;
	}
	/**
 * Get a model object for a class name.
 *
 * @param string $className Name of class you want model to be.
 * @return object Model instance
 */
	function &_getModelObject($className, $table = null) {
		//debugger::dump($table);
		if (!$table) {
			
			$table = Inflector::tableize($className);
		}
		//echo 'sometable '.$table.'<br />';
		$object =& new Model(array('name' => $className, 'table' => $table, 'ds' => 'default'));
		//																												HEEEEEEEEEEEEEEEEERRRRRRRRREEEEEEEEEEEEEE
		return $object;
	}
	
	/**
 * Get an Array of all the tables in the supplied connection
 * will halt the script if no tables are found.
 *
 * @param string $useDbConfig Connection name to scan.
 * @return array Array of tables in the database.
 */
	function getAllTables($useDbConfig = null) {
		App::import('Core', 'File');
		App::import('Model', 'CakeSchema', false);
		App::import('Model', 'ConnectionManager');
		
		$db = ConnectionManager::getDataSource('default');
		//debugger::dump($db);
		$schema =& new CakeSchema(array('name'=>'app'));
		$schema = $schema->load();
		return $schema;
	}
	
	/**
 * Find the paths to all the installed shell themes in the app.
 *
 * Bake themes are directories not named `skel` inside a `vendors/shells/templates` path.
 *
 * @return array Array of bake themes that are installed.
 */
	function _findThemes() {
		$paths = App::path('shells');
		$core = array_pop($paths);
		$separator = DS === '/' ? '/' : '\\\\';
		$core = preg_replace('#libs' . $separator . '$#', '', $core);

		$Folder =& new Folder($core . 'templates' . DS . 'default');
		$contents = $Folder->read();
		$themeFolders = $contents[0];

		
		$paths[] = $core;

		// TEMPORARY TODO remove when all paths are DS terminated
		foreach ($paths as $i => $path) {
			$paths[$i] = rtrim($path, DS) . DS;
		}

		$themes = array();
		foreach ($paths as $path) {
			$Folder =& new Folder($path . 'templates', false);
			$contents = $Folder->read();
			$subDirs = $contents[0];
			foreach ($subDirs as $dir) {
				if (empty($dir) || preg_match('@^skel$|_skel$@', $dir)) {
					continue;
				}
				$Folder =& new Folder($path . 'templates' . DS . $dir);
				$contents = $Folder->read();
				$subDirs = $contents[0];
				if (array_intersect($contents[0], $themeFolders)) {
					$templateDir = $path . 'templates' . DS . $dir . DS;
					$themes[$dir] = $templateDir;
				}
			}
		}
		return $themes;
	}


/**
 * Set variable values to the template scope
 *
 * @param mixed $one A string or an array of data.
 * @param mixed $two Value in case $one is a string (which then works as the key).
 *   Unused if $one is an associative array, otherwise serves as the values to $one's keys.
 * @return void
 */
	function setVars($one, $two = null) {
		$data = null;
		if (is_array($one)) {
			if (is_array($two)) {
				$data = array_combine($one, $two);
			} else {
				$data = $one;
			}
		} else {
			$data = array($one => $two);
		}

		if ($data == null) {
			return false;
		}
		//debugger::dump($data);
		$this->templateVars = $data + $this->templateVars;
	}

/**
 * Runs the template
 *
 * @param string $directory directory / type of thing you want
 * @param string $filename template name
 * @param string $vars Additional vars to set to template scope.
 * @access public
 * @return contents of generated code template
 */
	function generate($directory, $filename, $vars = null) {
		if ($vars !== null) {
			$this->setVars($vars);
		}
		if (empty($this->templatePaths)) {
			$this->templatePaths = $this->_findThemes();
		}
		
		$themePath = $this->getThemePath();
		$templateFile = $this->_findTemplate($themePath, $directory, $filename);
		if ($templateFile) {
			extract($this->templateVars);
			ob_start();
			ob_implicit_flush(0);
			include($templateFile);
			$content = ob_get_clean();
			return $content;
		}
		return '';
	}
/**
 * Find the theme name for the current operation.
 * If there is only one theme in $templatePaths it will be used.
 * If there is a -theme param in the cli args, it will be used.
 * If there is more than one installed theme user interaction will happen
 *
 * @return string returns the path to the selected theme.
 */
	function getThemePath() {
		if (count($this->templatePaths) == 1) {
			$paths = array_values($this->templatePaths);
			return $paths[0];
		}
		if (!empty($this->params['theme']) && isset($this->templatePaths[$this->params['theme']])) {
			return $this->templatePaths[$this->params['theme']];
		}

		$this->hr();
		$this->out(__('You have more than one set of templates installed.', true));
		$this->out(__('Please choose the template set you wish to use:', true));
		$this->hr();

		$i = 1;
		$indexedPaths = array();
		foreach ($this->templatePaths as $key => $path) {
			$this->out($i . '. ' . $key);
			$indexedPaths[$i] = $path;
			$i++;
		}
		$index = $this->in(__('Which bake theme would you like to use?', true), range(1, $i - 1), 1);
		$themeNames = array_keys($this->templatePaths);
		$this->Dispatch->params['theme'] = $themeNames[$index - 1];
		return $indexedPaths[$index];
	}

/**
 * Find a template inside a directory inside a path.
 * Will scan all other theme dirs if the template is not found in the first directory.
 *
 * @param string $path The initial path to look for the file on. If it is not found fallbacks will be used.
 * @param string $directory Subdirectory to look for ie. 'views', 'objects'
 * @param string $filename lower_case_underscored filename you want.
 * @access public
 * @return string filename will exit program if template is not found.
 */
	function _findTemplate($path, $directory, $filename) {
		$themeFile = $path . $directory . DS . $filename . '.ctp';
		if (file_exists($themeFile)) {
			return $themeFile;
		}
		foreach ($this->templatePaths as $path) {
			$templatePath = $path . $directory . DS . $filename . '.ctp';
			if (file_exists($templatePath)) {
				return $templatePath;
			}
		}
		debugger::dump('Could not find template for '. $filename. ' view.');
		
		return false;
	}
	/**
 * Creates a file at given path
 *
 * @param string $path Where to put the file.
 * @param string $contents Content to put in the file.
 * @return boolean Success
 * @access public
 */
	function createFile($path, $folderpath, $contents) {
		$path = str_replace(DS . DS, DS, $path);

		if (!class_exists('File')) {
			require LIBS . 'file.php';
		}

		if ($File = new File($path, true)) {
			
			$data = $File->prepare($contents);
			$File->write($data);
			
			//debugger::dump('written!');
			//$this->out(sprintf(__('Wrote `%s`', true), $path));
			return true;
		} else {
			//debugger::dump('Err!');
			//$this->err(sprintf(__('Could not write to `%s`.', true), $path), 2);
			return false;
		}
	}

}
?>