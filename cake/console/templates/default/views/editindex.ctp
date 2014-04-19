<?php
/**
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
 * @subpackage    cake.cake.console.libs.templates.views
 * @since         CakePHP(tm) v 1.2.0.5234
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
?>
<div class="<?php echo $pluralVar;?> index">
	<h2><?php echo "<?php __('{$pluralHumanName}');?>";?></h2>
     <?php echo "<?php echo \$this->Form->create(null, array('action'=>'deleteall')); ?>\n"; ?>
	<table class="table" cellpadding="0" cellspacing="0">
	<tr>
    <th></th>
	<?php  foreach ($fields as $field):?>
		<th><?php echo "<?php echo \$this->Paginator->sort('{$field}');?>";?></th>
	<?php endforeach;?>
    	<?php 
			if (!empty($associations['hasAndBelongsToMany'])) {
				foreach($associations['hasAndBelongsToMany'] as $alias => $detail){
					echo "<th><?php echo 'Related $alias'.'s';?></th>";
				}
			}
		
		?>
		<th class="actions"><?php echo "<?php __('Actions');?>";?></th>
	</tr>
	<?php
	echo "<?php
	\$i = 0;
	foreach (\${$pluralVar} as \${$singularVar}):
		\$class = null;
		if (\$i++ % 2 == 0) {
			\$class = ' class=\"altrow\"';
		}
	?>\n";
	echo "\t<tr<?php echo \$class;?>>\n";
	echo "<td><?php echo \$this->Form->input(\${$singularVar}['{$modelClass}']['{$primaryKey}'], array('type'=>'checkbox','label'=>false)); ?></td>\n";
		foreach ($fields as $field) {
			$isKey = false;
			if (!empty($associations['belongsTo'])) {
				foreach ($associations['belongsTo'] as $alias => $details) {
					if ($field === $details['foreignKey']) {
						$isKey = true;
						/*echo "\t\t<td>\n\t\t\t<?php echo \$this->Html->link(\${$singularVar}['{$alias}']['{$details['displayField']}'], array('controller' => '{$details['controller']}', 'action' => 'view', \${$singularVar}['{$alias}']['{$details['primaryKey']}'])); ?>\n\t\t</td>\n";
						*/
						echo "\t\t<td><?php echo \$this->Html->link(\${$singularVar}['{$alias}']['{$displayField}'], '#', array('id'=>'{$field}','data-url'=>'editindexsavefld', 'data-type'=>'select2', 'data-pk'=> \${$singularVar}['{$modelClass}']['{$primaryKey}'], 'class'=>'editable editable-click dclass-".$alias."', 'style'=>'display: inline;')); ?></td>\n";
						
						/*
						echo "\t\t<td><?php echo \$this->Html->link(\${$singularVar}['{$alias}']['{$displayField}'], '#', array('data-source'=>json_encode(\$".strtolower($alias)."s) ,'id'=>'{$field}','data-url'=>'editindexsavefld', 'data-type'=>'select2', 'data-pk'=> \${$singularVar}['{$modelClass}']['{$primaryKey}'], 'class'=>'editable editable-click', 'style'=>'display: inline;')); ?></td>\n";
						*/
						break;
					}
				}
			}
			if ($isKey !== true) {
				/*
					echo "\t\t<td><?php echo \${$singularVar}['{$modelClass}']['{$field}']; ?>&nbsp;</td>\n";
				*/
				echo "\t\t<td><?php echo \$this->Html->link(\${$singularVar}['{$modelClass}']['{$field}'], '#', array('id'=>'{$field}','data-url'=>$this->here.'/editindexsavefld', 'data-type'=>'text', 'data-pk'=> \${$singularVar}['{$modelClass}']['{$primaryKey}'], 'class'=>'editable editable-click jclass', 'style'=>'display: inline;')); ?></td>\n";
			}
			
			
		}
		
		if (!empty($associations['hasAndBelongsToMany'])) {
			foreach ($associations['hasAndBelongsToMany'] as $alias => $details) {
				
				/*echo "\t\t<td><?php foreach(\$kiddata[\$i-1]['$alias'] as \$alias){ echo \$this->Html->link(\$alias['{$details['displayField']}'], array('controller' => '{$alias}s', 'action' => 'view', \$alias['{$details['primaryKey']}'])); } ?></td>\n";
				*/
				echo "
				 <td> <?php \$arr = array(); 
				 foreach(\${$singularVar}data[\$i-1]['$alias'] as \${$alias}){ \$arr[] = \${$alias}['{$details['displayField']}']; }
					\$str = implode(',',\$arr); 
					echo \$this->Html->link(\$str, '#', array( 'id'=>'{$alias}__{$details['displayField']}','data-url'=>'savehabtmfld', 'data-type'=>'select2', 'data-pk'=> \${$singularVar}['{$modelClass}']['{$primaryKey}'], 'class'=>'editable editable-click mclass-{$alias}', 'style'=>'display: inline;')); ?></td>
				";
				
			}
			
		}

		echo "\t\t<td class=\"actions\">\n";
		echo "\t\t\t<?php echo \$this->Html->link(__('View', true), array('action' => 'view', \${$singularVar}['{$modelClass}']['{$primaryKey}'])); ?>\n";
		echo "\t\t\t<?php echo \$this->Html->link(__('Edit', true), array('action' => 'edit', \${$singularVar}['{$modelClass}']['{$primaryKey}'])); ?>\n";
		echo "\t\t\t<?php echo \$this->Html->link(__('Delete', true), array('action' => 'delete', \${$singularVar}['{$modelClass}']['{$primaryKey}']), null, sprintf(__('Are you sure you want to delete # %s?', true), \${$singularVar}['{$modelClass}']['{$primaryKey}'])); ?>\n";
		echo "\t\t</td>\n";
	echo "\t</tr>\n";

	echo "<?php endforeach; ?>\n";
	?>
	</table>
    <?php echo "<?php echo \$this->Form->end('Delete'); ?>"; ?>
	<p>
	<?php echo "<?php
	echo \$this->Paginator->counter(array(
	'format' => __('Page %page% of %pages%, showing %current% records out of %count% total, starting on record %start%, ending on %end%', true)
	));
	?>";?>
	</p>

	<div class="paging">
	<?php echo "\t<?php echo \$this->Paginator->prev('<< ' . __('previous', true), array(), null, array('class'=>'disabled'));?>\n";?>
	 | <?php echo "\t<?php echo \$this->Paginator->numbers();?>\n"?> |
	<?php echo "\t<?php echo \$this->Paginator->next(__('next', true) . ' >>', array(), null, array('class' => 'disabled'));?>\n";?>
	</div>
</div>
<div class="actions">
	<h3><?php echo "<?php __('Actions'); ?>"; ?></h3>
	<ul>
		<li><?php echo "<?php echo \$this->Html->link(__('New " . $singularHumanName . "', true), array('action' => 'add')); ?>";?></li>
<?php
	$done = array();
	foreach ($associations as $type => $data) {
		foreach ($data as $alias => $details) {
			if ($details['controller'] != $this->name && !in_array($details['controller'], $done)) {
				echo "\t\t<li><?php echo \$this->Html->link(__('List " . Inflector::humanize($details['controller']) . "', true), array('controller' => '{$details['controller']}', 'action' => 'index')); ?> </li>\n";
				echo "\t\t<li><?php echo \$this->Html->link(__('New " . Inflector::humanize(Inflector::underscore($alias)) . "', true), array('controller' => '{$details['controller']}', 'action' => 'add')); ?> </li>\n";
				$done[] = $details['controller'];
			}
		}
	}
?>
	</ul>
</div>
<script type="text/javascript">
$.fn.editable.defaults.mode = 'inline';

$('.jclass').editable();
<?php
if (!empty($associations['hasAndBelongsToMany'])) {
			foreach ($associations['hasAndBelongsToMany'] as $alias => $details) {
				
				/*echo "\$('#{$alias}__{$details['displayField']}').editable({
						inputclass: 'input-large',
							select2: {
								tags: <?php echo \$".strtolower($alias)."tr; ?>,
								tokenSeparators: [',', ' ']
							}
							});\n";	
				*/
				echo "\$('.mclass-".$alias."').editable({
						inputclass: 'input-large',
							select2: {
								tags: <?php echo \$".strtolower($alias)."str; ?>,
								tokenSeparators: [',', ' ']
							}
							});\n";	
							
			}
			
		}

if (!empty($associations['belongsTo'])) {
			foreach ($associations['belongsTo'] as $alias => $details) {
			/*	
			echo "var {$alias}slist = [];
			$.each(<?php echo json_encode(\$".strtolower($alias)."s); ?>, function(k, v) {
				{$alias}slist.push({id: k, text: v});
			}); 
			
			$('#".strtolower($alias)."_id').editable({
				source: {$alias}slist,
				select2: {
					width: 200,
					placeholder: 'Select $alias',
					allowClear: true
				} 
			});\n ";
			*/
			echo "var {$alias}slist = [];
			$.each(<?php echo json_encode(\$".strtolower($alias)."s); ?>, function(k, v) {
				{$alias}slist.push({id: k, text: v});
			}); 
			
			$('.dclass-".$alias."').editable({
				source: {$alias}slist,
				select2: {
					width: 200,
					placeholder: 'Select $alias',
					allowClear: true
				} 
			});\n ";
				
			}
			
		}
?>

</script>