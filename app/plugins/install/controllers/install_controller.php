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
				$mymodel = ClassRegistry::Init($table);
				
				$this->bakemodel($mymodel);
				
				$this->bakecontroller($this->_controllerName($mymodel->name),$this->bakeActions($this->_controllerName($mymodel->name)));
				//the controller template was modified to exclude the extenstion from "installAppController"...
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
	}/**
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
		
		//debugger::dump($this->_tables->tables);
		foreach ($this->_tables->tables as $mname => $otherTable) {
			//debugger::dump($mname);
			$tempOtherModel = $this->_getModelObject($this->_modelName($mname), $mname);
			
			$modelFieldsTemp = $tempOtherModel->schema(true);
			$pattern = '/_' . preg_quote($model->table, '/') . '|' . preg_quote($model->table, '/') . '_/';
			//debugger::dump($pattern);
			
			$possibleJoinTable = preg_match($pattern , $mname);
			if ($possibleJoinTable == true) {
				continue;
			}
			
			foreach ($modelFieldsTemp as $fieldName => $field) {
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
		$object =& new Model(array('name' => $className, 'table' => $table, 'ds' => 'default'));
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