<?php
/**
 * Bake Template for Controller action generation.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.console.libs.template.objects
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
?>
	 public function beforeFilter() {
       parent::beforeFilter();

    }

	function <?php echo $admin ?>index() {
		$this-><?php echo $currentModelName ?>->recursive = 0;
		$this->set('<?php echo $pluralName ?>', $this->paginate());
	}
    
    function mobileindex() {
		$this-><?php echo $currentModelName ?>->recursive = -1;
		$this->autoRender = false;
		$check = $this-><?php echo $currentModelName ?>->find('all', array('limit'=>200));
		$save = array();
		if($check) {
			
			$response = $check;
				
		} else {
			$response = array(
				'logged' => false,
				'message' => 'Invalid user'
			);
		}
		echo json_encode($response);
	}
    
    function mobileadd() {
		$this->autoRender = false;
		$this->data['<?php echo $currentModelName ?>']=$_POST;
		$this-><?php echo $currentModelName ?>->create();
		if ($this-><?php echo $currentModelName ?>->save($this->data)) {
			$check = array(
			'logged' => false,
			'message' => 'Saved!',
			'id'=>$this-><?php echo $currentModelName ?>->getLastInsertId()
			);	
		} else {
			$this->Session->setFlash(__('The <?php echo $currentModelName ?> could not be saved. Please, try again.', true));
		}
		if($check) {
			
			$response = $check;
				
		} else {
			$response = array(
				'logged' => false,
				'message' => 'Invalid user'
			);
		}
		echo json_encode($response);
	}
    
     function mobilesave() {
		$this->autoRender = false;
        $this-><?php echo $currentModelName ?>->id=$_POST['id'];
		$this->data['<?php echo $currentModelName ?>']=$_POST;
		if ($this-><?php echo $currentModelName ?>->save($this->data)) {
			$check = array(
			'logged' => false,
			'message' => 'Saved!',
			);	
		} else {
			$this->Session->setFlash(__('The <?php echo $currentModelName ?> could not be saved. Please, try again.', true));
		}
		if($check) {
			
			$response = $check;
				
		} else {
			$response = array(
				'logged' => false,
				'message' => 'Invalid <?php echo $currentModelName ?>'
			);
		}
		echo json_encode($response);
	}
    
    function mobiledelete($id = null) {
		if (!$id) {
			$response = array(
						'logged' => false,
						'message' => '<?php echo $currentModelName ?> did not exist remotely!'
					);
			
		}
		if ($this-><?php echo $currentModelName ?>->delete($id)) {
			$response = array(
						'logged' => false,
						'message' => '<?php echo $currentModelName ?> deleted!'
					);
					
		}else{
			$response = array(
						'logged' => false,
						'id'=>$id,
						'message' => '<?php echo $currentModelName ?> not deleted!'
					);
		}
					
		echo json_encode($response);
	}
    
    function <?php echo $admin ?>editindexsavefld() {
		$this->autoRender = false;
		$this-><?php echo $currentModelName ?>->id = $_POST['pk'];
		
		if($this-><?php echo $currentModelName ?>->saveField($_POST['name'],$_POST['value'])) {
			$response = true;	
		} else {
			$response = false;
		}
		echo json_encode($response);
	}
    
    <?php $compact = array(); ?>
    function <?php echo $admin ?>editindex() {
		//$this-><?php echo $currentModelName ?>->recursive = 0;
		$this->set('<?php echo $pluralName ?>', $this->paginate());
         //check if this is a relationship table
        <?php if(preg_match('/\s/',$singularHumanName) == 1):?>
					$<?php echo str_replace( ' ' , '' , $singularHumanName); ?>data = $this-><?php echo $currentModelName ?>->find('all');
			  <?php  else: ?>
			   		 $<?php echo strtolower($singularHumanName); ?>data = $this-><?php echo $currentModelName ?>->find('all');
		<?php endif;?>
        
       
       
		<?php if(preg_match('/\s/',$singularHumanName) == 1){
					$compact[] = "'".str_replace( ' ' , '' , $singularHumanName).'data'."'";
			   }else{
			   		$compact[] =  "'".strtolower($singularHumanName).'data'."'";
			   }
					
		?>
        
        <?php
	foreach (array('belongsTo', 'hasAndBelongsToMany') as $assoc):
		foreach ($modelObj->{$assoc} as $associationName => $relation):
			if (!empty($associationName)):
				$otherModelName = $this->_modelName($associationName);
				$otherPluralName = $this->_pluralName($associationName);
				echo "\t\t\${$otherPluralName} = \$this->{$currentModelName}->{$otherModelName}->find('list');\n";
				
				if($assoc =='hasAndBelongsToMany'):
					echo "
						\$arr = array();
						foreach(\${$otherPluralName} as \$item => \$i){
							\$arr[] = \$i;
						}
						\${$otherPluralName}tr = json_encode(\$arr);
					";
					$compact[] = "'{$otherPluralName}tr'";
				else:
					$compact[] = "'{$otherPluralName}'";
				endif;
				
				
			endif;
		endforeach;
	endforeach;
	if (!empty($compact)):
		echo "\t\t\$this->set(compact(".join(', ', $compact)."));\n";
	endif;
?>
        
        
	}
    
     function savehabtmfld(){
  
		$this->autoRender = false;
		$this-><?php echo $currentModelName ?>->id = $_POST['pk'];
        $tr = substr($_POST['name'],0,strpos($_POST['name'],'__'));
		$ids = $this-><?php echo $currentModelName ?>->$tr->find('list', array('fields'=>array('id'), 'conditions'=>array(str_replace('__','.',$_POST['name'])=>$_POST['value'])));
		$this->data = array('<?php echo $currentModelName ?>'=>array('id'=>$_POST['pk']),substr($_POST['name'],0,strpos($_POST['name'],'__'))=>array(substr($_POST['name'],0,strpos($_POST['name'],'__'))=>$ids));
		
		if($this-><?php echo $currentModelName ?>->save($this->data)) {
			$response = true;
				
		} else {
			$response = false;
		}
		echo json_encode($response);
	}
    
    
     function <?php echo $admin ?>deleteall() {
		$this->autoRender = false;
        
  		$this->autoRender = false;
		$arr = array();
		foreach($this->data['<?php echo $currentModelName ?>'] as $<?php echo $singularName; ?>_id => $del){
			if($del == 1 ){$arr[] = $<?php echo $singularName; ?>_id;}
		}
		if($this-><?php echo $currentModelName ?>->deleteAll(array('<?php echo $currentModelName; ?>.id'=>$arr))) {
			$this->Session->setFlash(__('Deleted.', true));
			$this->redirect(array('action' => 'index'));
		
		}else{
			$this->Session->setFlash(__('Could not be deleted.', true));
			$this->redirect(array('action' => 'index'));
		}

	}
    

	function <?php echo $admin ?>view($id = null) {
		if (!$id) {
<?php if ($wannaUseSession): ?>
			$this->Session->setFlash(__('Invalid <?php echo strtolower($singularHumanName) ?>', true));
			$this->redirect(array('action' => 'index'));
<?php else: ?>
			$this->flash(__('Invalid <?php echo strtolower($singularHumanName); ?>', true), array('action' => 'index'));
<?php endif; ?>
		}
		$this->set('<?php echo $singularName; ?>', $this-><?php echo $currentModelName; ?>->read(null, $id));
	}

<?php $compact = array(); ?>
	function <?php echo $admin ?>add() {
		if (!empty($this->data)) {
			$this-><?php echo $currentModelName; ?>->create();
			if ($this-><?php echo $currentModelName; ?>->save($this->data)) {
<?php if ($wannaUseSession): ?>
				$this->Session->setFlash(__('The <?php echo strtolower($singularHumanName); ?> has been saved', true));
				$this->redirect(array('action' => 'index'));
<?php else: ?>
				$this->flash(__('<?php echo ucfirst(strtolower($currentModelName)); ?> saved.', true), array('action' => 'index'));
<?php endif; ?>
			} else {
<?php if ($wannaUseSession): ?>
				$this->Session->setFlash(__('The <?php echo strtolower($singularHumanName); ?> could not be saved. Please, try again.', true));
<?php endif; ?>
			}
		}
<?php
	foreach (array('belongsTo', 'hasAndBelongsToMany') as $assoc):
		foreach ($modelObj->{$assoc} as $associationName => $relation):
			if (!empty($associationName)):
				$otherModelName = $this->_modelName($associationName);
				$otherPluralName = $this->_pluralName($associationName);
				echo "\t\t\${$otherPluralName} = \$this->{$currentModelName}->{$otherModelName}->find('list');\n";
				$compact[] = "'{$otherPluralName}'";
			endif;
		endforeach;
	endforeach;
	if (!empty($compact)):
		echo "\t\t\$this->set(compact(".join(', ', $compact)."));\n";
	endif;
?>
	}

<?php $compact = array(); ?>
	function <?php echo $admin; ?>edit($id = null) {
		if (!$id && empty($this->data)) {
<?php if ($wannaUseSession): ?>
			$this->Session->setFlash(__('Invalid <?php echo strtolower($singularHumanName); ?>', true));
			$this->redirect(array('action' => 'index'));
<?php else: ?>
			$this->flash(sprintf(__('Invalid <?php echo strtolower($singularHumanName); ?>', true)), array('action' => 'index'));
<?php endif; ?>
		}
		if (!empty($this->data)) {
			if ($this-><?php echo $currentModelName; ?>->save($this->data)) {
<?php if ($wannaUseSession): ?>
				$this->Session->setFlash(__('The <?php echo strtolower($singularHumanName); ?> has been saved', true));
				$this->redirect(array('action' => 'index'));
<?php else: ?>
				$this->flash(__('The <?php echo strtolower($singularHumanName); ?> has been saved.', true), array('action' => 'index'));
<?php endif; ?>
			} else {
<?php if ($wannaUseSession): ?>
				$this->Session->setFlash(__('The <?php echo strtolower($singularHumanName); ?> could not be saved. Please, try again.', true));
<?php endif; ?>
			}
		}
		if (empty($this->data)) {
			$this->data = $this-><?php echo $currentModelName; ?>->read(null, $id);
		}
<?php
		foreach (array('belongsTo', 'hasAndBelongsToMany') as $assoc):
			foreach ($modelObj->{$assoc} as $associationName => $relation):
				if (!empty($associationName)):
					$otherModelName = $this->_modelName($associationName);
					$otherPluralName = $this->_pluralName($associationName);
					echo "\t\t\${$otherPluralName} = \$this->{$currentModelName}->{$otherModelName}->find('list');\n";
					$compact[] = "'{$otherPluralName}'";
				endif;
			endforeach;
		endforeach;
		if (!empty($compact)):
			echo "\t\t\$this->set(compact(".join(', ', $compact)."));\n";
		endif;
	?>
	}

	function <?php echo $admin; ?>delete($id = null) {
		if (!$id) {
<?php if ($wannaUseSession): ?>
			$this->Session->setFlash(__('Invalid id for <?php echo strtolower($singularHumanName); ?>', true));
			$this->redirect(array('action'=>'index'));
<?php else: ?>
			$this->flash(sprintf(__('Invalid <?php echo strtolower($singularHumanName); ?>', true)), array('action' => 'index'));
<?php endif; ?>
		}
		if ($this-><?php echo $currentModelName; ?>->delete($id)) {
<?php if ($wannaUseSession): ?>
			$this->Session->setFlash(__('<?php echo ucfirst(strtolower($singularHumanName)); ?> deleted', true));
			$this->redirect(array('action'=>'index'));
<?php else: ?>
			$this->flash(__('<?php echo ucfirst(strtolower($singularHumanName)); ?> deleted', true), array('action' => 'index'));
<?php endif; ?>
		}
<?php if ($wannaUseSession): ?>
		$this->Session->setFlash(__('<?php echo ucfirst(strtolower($singularHumanName)); ?> was not deleted', true));
<?php else: ?>
		$this->flash(__('<?php echo ucfirst(strtolower($singularHumanName)); ?> was not deleted', true), array('action' => 'index'));
<?php endif; ?>
		$this->redirect(array('action' => 'index'));
	}