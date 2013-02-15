<?php if (!empty($this->request->data)): ?>
	<?php foreach($this->request->data as $article): ?>
		<div class="span8 no-marg-left">
			<?= $this->Html->link('<h2>' . $article['Article']['title'] . '</h2>', array(
				'controller' => 'articles', 
				'action' => 'view', 
				$article['Article']['slug']
			), array('escape' => false)) ?>

			<p class="lead">
				@ <em><?= $this->Admin->time($article['Article']['created']) ?></em>
			</p>

			<blockquote><?= $this->Field->getTextAreaData($article) ?></blockquote>

			<div id="post-options">
		        <span class="pull-left">
		            <?= $this->Html->link('Read More', array('controller' => 'articles', 'action' => 'view', $article['Article']['slug']), array('class' => 'btn btn-primary')) ?>
		            <span style="margin-left: 10px">
		                <i class="icon-search icon-comment"></i>&nbsp;
		                <?= $this->Html->link($article['Comments'] . ' Comments', array('controller' => 'articles', 'action' => 'view', $article['Article']['slug'])) ?>
		            </span>
		            <span style="margin-left: 10px">
		                <i class="icon-search icon-user"></i>&nbsp;
		                Posted by <?= $this->Html->link($article['User']['username'], array('controller' => 'users', 'action' => 'profile', $article['User']['username'])) ?>
		            </span>
		        </span>
		        <span class="pull-right">
		        	<?php if (!empty($article['Article']['tags'])): ?>
			            <?php foreach($article['Article']['tags'] as $tag): ?>
			                <?= $this->Html->link('<span class="btn btn-success">'.$tag.'</span>', array('controller' => 'articles', 'action' => 'tag', $tag), array('class' => 'tags', 'escape' => false)) ?>
			            <?php endforeach ?>
		        	<?php endif ?>
		        </span>
		    </div>
		</div>

		<div class="clearfix"></div>

		<hr>
	<?php endforeach ?>
<?php endif ?>