<div class="queueTasks view">
<h2><?php echo __('Queue Task'); ?></h2>
	<dl>
		<dt><?php echo __('Id'); ?></dt>
		<dd>
			<?php echo h($queueTask['QueueTask']['id']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('User Id'); ?></dt>
		<dd>
			<?php echo h($queueTask['QueueTask']['user_id']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Created'); ?></dt>
		<dd>
			<?php echo $this->Time->niceShort($queueTask['QueueTask']['created']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Modified'); ?></dt>
		<dd>
			<?php echo $this->Time->niceShort($queueTask['QueueTask']['modified']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Executed'); ?></dt>
		<dd>
			<?php if (!empty($queueTask['QueueTask']['executed'])) echo $this->Time->niceShort($queueTask['QueueTask']['executed']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Scheduled'); ?></dt>
		<dd>
			<?php if (!empty($queueTask['QueueTask']['scheduled'])) echo $this->Time->niceShort($queueTask['QueueTask']['scheduled']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Scheduled End'); ?></dt>
		<dd>
			<?php if (!empty($queueTask['QueueTask']['scheduled_end'])) echo $this->Time->niceShort($queueTask['QueueTask']['scheduled_end']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Reschedule'); ?></dt>
		<dd>
			<?php if (!empty($queueTask['QueueTask']['reshcedule'])) echo $this->Time->niceShort($queueTask['QueueTask']['reschedule']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Start Time'); ?></dt>
		<dd>
			<?php echo h($queueTask['QueueTask']['start_time']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('End Time'); ?></dt>
		<dd>
			<?php echo h($queueTask['QueueTask']['end_time']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Cpu Limit'); ?></dt>
		<dd>
			<?php echo h($queueTask['QueueTask']['cpu_limit']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Is Restricted'); ?></dt>
		<dd>
			<?php echo h($queueTask['QueueTask']['is_restricted']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Priority'); ?></dt>
		<dd>
			<?php echo h($queueTask['QueueTask']['priority']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Status'); ?></dt>
		<dd>
			<?php echo $statuses[$queueTask['QueueTask']['status']]; ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Type'); ?></dt>
		<dd>
			<?php echo $types[$queueTask['QueueTask']['type']]; ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Command'); ?></dt>
		<dd>
			<?php echo h($queueTask['QueueTask']['command']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Result'); ?></dt>
		<dd>
			<?php echo h($queueTask['QueueTask']['result']); ?>
			&nbsp;
		</dd>
	</dl>
</div>
<div class="actions">
	<h3><?php echo __('Actions'); ?></h3>
	<ul>
		<li><?php echo $this->Html->link(__('Edit Queue Task'), array('action' => 'edit', $queueTask['QueueTask']['id'])); ?> </li>
		<li><?php echo $this->Html->link(__('List Queue Tasks'), array('action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Queue Task'), array('action' => 'add')); ?> </li>
	</ul>
</div>
