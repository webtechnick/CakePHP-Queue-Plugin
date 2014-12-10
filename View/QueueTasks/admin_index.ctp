<div class="queueTasks index">
	<h2><?php echo __('Queue Tasks'); ?></h2>
	<table cellpadding="0" cellspacing="0">
	<thead>
	<tr>
			<th><?php echo $this->Paginator->sort('id'); ?></th>
			<th><?php echo $this->Paginator->sort('user_id'); ?></th>
			<th><?php echo $this->Paginator->sort('created'); ?></th>
			<th><?php echo $this->Paginator->sort('modified'); ?></th>
			<th><?php echo $this->Paginator->sort('executed'); ?></th>
			<th><?php echo $this->Paginator->sort('scheduled'); ?></th>
			<th><?php echo $this->Paginator->sort('scheduled_end'); ?></th>
			<th><?php echo $this->Paginator->sort('reschedule'); ?></th>
			<th><?php echo $this->Paginator->sort('start_time'); ?></th>
			<th><?php echo $this->Paginator->sort('end_time'); ?></th>
			<th><?php echo $this->Paginator->sort('cpu_limit'); ?></th>
			<th><?php echo $this->Paginator->sort('is_restricted'); ?></th>
			<th><?php echo $this->Paginator->sort('priority'); ?></th>
			<th><?php echo $this->Paginator->sort('status'); ?></th>
			<th><?php echo $this->Paginator->sort('type'); ?></th>
			<th><?php echo $this->Paginator->sort('command'); ?></th>
			<th><?php echo $this->Paginator->sort('result'); ?></th>
			<th class="actions"><?php echo __('Actions'); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php foreach ($queueTasks as $queueTask): ?>
	<tr>
		<td><?php echo h($queueTask['QueueTask']['id']); ?>&nbsp;</td>
		<td><?php echo h($queueTask['QueueTask']['user_id']); ?>&nbsp;</td>
		<td><?php echo h($queueTask['QueueTask']['created']); ?>&nbsp;</td>
		<td><?php echo h($queueTask['QueueTask']['modified']); ?>&nbsp;</td>
		<td><?php echo h($queueTask['QueueTask']['executed']); ?>&nbsp;</td>
		<td><?php echo h($queueTask['QueueTask']['scheduled']); ?>&nbsp;</td>
		<td><?php echo h($queueTask['QueueTask']['scheduled_end']); ?>&nbsp;</td>
		<td><?php echo h($queueTask['QueueTask']['reschedule']); ?>&nbsp;</td>
		<td><?php echo h($queueTask['QueueTask']['start_time']); ?>&nbsp;</td>
		<td><?php echo h($queueTask['QueueTask']['end_time']); ?>&nbsp;</td>
		<td><?php echo h($queueTask['QueueTask']['cpu_limit']); ?>&nbsp;</td>
		<td><?php echo h($queueTask['QueueTask']['is_restricted']); ?>&nbsp;</td>
		<td><?php echo h($queueTask['QueueTask']['priority']); ?>&nbsp;</td>
		<td><?php echo h($queueTask['QueueTask']['status']); ?>&nbsp;</td>
		<td><?php echo h($queueTask['QueueTask']['type']); ?>&nbsp;</td>
		<td><?php echo h($queueTask['QueueTask']['command']); ?>&nbsp;</td>
		<td><?php echo h($queueTask['QueueTask']['result']); ?>&nbsp;</td>
		<td class="actions">
			<?php echo $this->Html->link(__('View'), array('action' => 'view', $queueTask['QueueTask']['id'])); ?>
			<?php echo $this->Html->link(__('Edit'), array('action' => 'edit', $queueTask['QueueTask']['id'])); ?>
			<?php echo $this->Form->postLink(__('Delete'), array('action' => 'delete', $queueTask['QueueTask']['id']), array(), __('Are you sure you want to delete # %s?', $queueTask['QueueTask']['id'])); ?>
		</td>
	</tr>
<?php endforeach; ?>
	</tbody>
	</table>
	<p>
	<?php
	echo $this->Paginator->counter(array(
	'format' => __('Page {:page} of {:pages}, showing {:current} records out of {:count} total, starting on record {:start}, ending on {:end}')
	));
	?>	</p>
	<div class="paging">
	<?php
		echo $this->Paginator->prev('< ' . __('previous'), array(), null, array('class' => 'prev disabled'));
		echo $this->Paginator->numbers(array('separator' => ''));
		echo $this->Paginator->next(__('next') . ' >', array(), null, array('class' => 'next disabled'));
	?>
	</div>
</div>
<div class="actions">
	<h3><?php echo __('Actions'); ?></h3>
	<ul>
		<li><?php echo $this->Html->link(__('New Queue Task'), array('action' => 'add')); ?></li>
	</ul>
</div>
