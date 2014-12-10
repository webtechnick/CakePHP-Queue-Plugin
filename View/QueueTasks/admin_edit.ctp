<div class="queueTasks form">
<?php echo $this->Form->create('QueueTask'); ?>
	<fieldset>
		<legend><?php echo __('Admin Edit Queue Task'); ?></legend>
	<?php
		echo $this->Form->input('id');
		echo $this->Form->input('user_id');
		echo $this->Form->input('executed');
		echo $this->Form->input('scheduled');
		echo $this->Form->input('scheduled_end');
		echo $this->Form->input('reschedule');
		echo $this->Form->input('start_time');
		echo $this->Form->input('end_time');
		echo $this->Form->input('cpu_limit');
		echo $this->Form->input('is_restricted');
		echo $this->Form->input('priority');
		echo $this->Form->input('status');
		echo $this->Form->input('type');
		echo $this->Form->input('command');
		echo $this->Form->input('result');
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit')); ?>
</div>
<div class="actions">
	<h3><?php echo __('Actions'); ?></h3>
	<ul>

		<li><?php echo $this->Form->postLink(__('Delete'), array('action' => 'delete', $this->Form->value('QueueTask.id')), array(), __('Are you sure you want to delete # %s?', $this->Form->value('QueueTask.id'))); ?></li>
		<li><?php echo $this->Html->link(__('List Queue Tasks'), array('action' => 'index')); ?></li>
	</ul>
</div>
