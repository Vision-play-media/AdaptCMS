<?php if (empty($this->request->data) && empty($sql['error'])): ?>
	<?= $this->Form->create('Install', array('class' => 'well')) ?>
		<?php if (!empty($install_text)): ?>
			<h2 class="no-bg">From Manufacturer</h2>

			<?= $install_text ?>
		<?php endif ?>

		<h1 class="no-bg">Notice</h1>

		<p>
			Please note that Insane Visions currently reviews all themes, but does not gurantee these themes to be fully working and any damage caused is not our responsibility. We advise all users to review information on the official page of the Theme and to ensure the best and safest chance of getting a theme, to use the official website.
		</p>

		<?= $this->Form->hidden('install') ?>
	<?= $this->Form->end('Install Theme') ?>
<?php else: ?>
	<div class="well">
		<?php if (!empty($sql)): ?>

			<?php foreach($sql['sql'] as $file => $count): ?>
				<h2 class="no-bg">
					<?= $file ?>
				</h2>

				<?php if ($count['total'] == $count['success']): ?>
					<span class="notice success">
						SQL Data Inserted Successfully
					</span>
				<?php else: ?>
					<span class="notice success">
						Unable to insert all SQL Data. <?= $count['success'] ?> of <?= $count['total'] ?> inserted. Go back and try again
					</span>
				<?php endif ?>
			<?php endforeach ?>

			<?php if (empty($sql['sql']['error'])): ?>
				<p>
					The Theme has been installed successfully! <?= $this->Html->link('Click here', array(
						'controller' => 'templates',
						'action' => 'index',
						'admin' => true
					)) ?> to return to the Appearance Page.
				</p>
			<?php endif ?>
		<?php endif ?>
	</div>
<?php endif ?>