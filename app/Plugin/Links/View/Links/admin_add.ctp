<script>
 $(document).ready(function(){
    $("#LinkAdminAddForm").validate();

    $("#LinkUrl").rules("add", {
    	required: true,
    	url: true
    });

    $("#LinkImageUrl").rules("add", {
    	url: true
    });

    $(".image_url,.file_id").hide();

    $("#LinkType").on('change', function() {
    	if ($(this).val()) {
    		if ($(this).val() == 'file') {
    			$(".file_id").show();
    			$(".image_url").hide();

    			$("#LinkImageUrl").val('');
    		} else {
    			$(".file_id").hide();
    			$(".selected-images").html('');

    			$(".image_url").show();
    		}
    	} else {
    		$(".image_url,.file_id").hide();
    		$(".selected-images").html('');
    		$("#LinkImageUrl").val('');
    	}
    });

 });
 </script>

<h1>Add Link</h1>

<?php
	echo $this->Form->create('Link', array('class' => 'well'));

	echo $this->Form->input('title', array('class' => 'required'));
	echo $this->Form->input('url', array(
		'class' => 'required', 
		'label' => 'Website Address',
		'placeholder' => 'http://'
	));
	echo $this->Form->input('link_title');
	echo $this->Form->input('link_target', array(
		'options' => array(
			'_new' => '_new',
			'_blank' => '_blank'
		)
	));
?>

<?= $this->Form->input('type', array(
		'options' => array(
			'file' => 'Pick an Image',
			'external' => 'External Image URL'
		),
		'empty' => '- Choose Image Type (optional) -'
)) ?>

<?= $this->Form->input('image_url', array(
		'div' => array(
			'class' => 'text input image_url'
		),
		'placeholder' => 'http://'
)) ?>

<div class="file_id">
	<?= $this->Html->link('Attach Image <i class="icon icon-white icon-upload"></i>', '#media-modal', array('class' => 'btn btn-primary', 'escape' => false, 'data-toggle' => 'modal')) ?>

	<p>&nbsp;</p>
	<ul class="selected-images span12 thumbnails"></ul>
</div>

<div class="clearfix"></div>

<?= $this->Form->hidden('created', array('value' => $this->Time->format('Y-m-d H:i:s', time()))) ?>
<br />
<?= $this->Form->end(array(
	'label' => 'Submit',
	'class' => 'btn btn-primary'
)) ?>

<?= $this->element('media_modal', array('limit' => 1)) ?>