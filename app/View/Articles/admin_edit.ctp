<?php
	$this->TinyMce->editor();
	$time = date('Y-m-d H:i:s');
	$time_part = explode(" - ", date('Y-m-d - g:i A'));
	if ($this->request->data['Article']['status'] == 0) {
		$draft = "Keep As ";
	} else {
		$draft = "Save ";
	}
?>

<?= $this->Html->script('data-tagging.js') ?>
<?= $this->Html->css("data-tagging.css") ?>

<?= $this->Html->css("datepicker.css") ?>
<?= $this->Html->script('bootstrap-datepicker.js') ?>

<script>
 $(document).ready(function(){
 	<?php if (!$radio_fields): ?>
    	$("#ArticleEditForm").validate();
    <?php else: ?>
    	$("#ArticleEditForm").validate({
    		focusInvalid: false,
			invalidHandler: function(form, validator) {
				$(this).find(":input.error:first:not(:checkbox):not(:radio)").focus();
			},
    		errorPlacement: function(error, element) {
				if ($(element).attr('type') == 'radio' || $(element).attr('type') == 'checkbox') {
					error.insertAfter( $(element).parent().find('label').last() );
				} else {
					error.insertAfter( element );
				}
			}
    	});
    <?php endif ?>
    $("input[type=file]").live('change', function() {
    	if ($("#" + this.id).val()) {
    		$("#" + this.id.replace('Data', 'Delete')).attr('disabled', true).attr('checked', false);
    	}
    });
    $(".field_options").show();

    $.each($(".checkbox"), function(i, val) {
    	if (!$(this).hasClass('input')) {
    		$(this).replaceWith($(this).find('input,label'));
    	}
    });

    $("button").live('click', function(){
    	var btn = $(this).html();
    	if (btn == "Publish Later") {
    		$(".publish_time").show();
    		$("#ArticleStatus").val(1);
    	} else if(btn == "Publish Now") {
    		$(".publish_time").hide();
    		$("#ArticlePublishingDate").val('<?= $time_part[0] ?>');
    		$("#ArticlePublishingTime").val('<?= $time_part[1] ?>');
    		$("#ArticleStatus").val(1);
    	} else if(btn == "Save Draft") {
    		$(".publish_time").hide();
    		$("#ArticlePublishingDate").val('<?= $time_part[0] ?>');
    		$("#ArticlePublishingTime").val('<?= $time_part[1] ?>');
    		$("#ArticleStatus").val(0);
    	}
    });
    $('#ArticlePublishingDate').datepicker();

    <?php if ($this->request->data['Article']['publish_time'] > $time): ?>
    	$(".publish_time").show();
    	<?php
    		$time_part = explode(" - ", 
    			date(
    				'Y-m-d - g:i A', 
    				strtotime(
    					$this->request->data['Article']['publish_time']
    				)
    			)
    		);
    	?>
    <?php endif ?>
 });
 </script>

<h1>Edit Article</h1>

<?= $this->Form->create('Article', array('type' => 'file', 'action' => 'edit', 'class' => 'well')) ?>

<?= $this->Form->input('title', array('type' => 'text', 'class' => 'required')) ?>
<?= $this->Form->input('category_id', array('type' => 'hidden', 'value' => $category_id)) ?>

<?php
$jqueryFunction = 0;
$required = null;

if (count($fields) > 0):
	foreach ($fields as $field):
	$value = array();
	$dataId = array();
		foreach($this->request->data['ArticleValue'] as $key => $data) {
			if ($data['field_id'] == $field['Field']['id'] && !empty($data['data'])) {
				$dataId[] = $data['id'];

				if (json_decode($data['data'])) {
					$value = json_decode($data['data']);
				} else {
					$value[] = $data['data'];
				}

				unset($this->request->data['ArticleValue'][$key]);
			}
		}
?>

<?php if ($field['Field']['field_type'] != "check" && 
		  $field['Field']['field_type'] != "multi-dropdown" && 
		  !empty($dataId)): 
?>
	<?= $this->Form->input('ArticleValue.'.$dataId[0].'.id', array('type' => 'hidden', 'value' => $dataId[0])) ?>
	<?= $this->Form->input('ArticleValue.'.$dataId[0].'.field_id', array('type' => 'hidden', 'value' => $field['Field']['id'])) ?>
	<?php $field['Field']['id'] = $dataId[0] ?>
<?php elseif ($field['Field']['field_type'] != "check" &&
			  $field['Field']['field_type'] != "multi-dropdown"
):  ?>
	<?= $this->Form->input('ArticleValue.'.$field['Field']['id'].'.field_id', array('type' => 'hidden', 'value' => $field['Field']['id'])) ?>
<?php else: ?>
	<?php foreach ($dataId as $id): ?>
		<?= $this->Form->input('ArticleFieldData.'.$field['Field']['id'].'.id', array('type' => 'hidden', 'value' => $id)) ?>
	<?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($field['Field']['rules'])):
$rules = json_decode($field['Field']['rules']);
$rules_count = count($rules);
$addClass = 0;
?>
	<?php if (!empty($rules)): ?>
		<?php if($field['Field']['field_type'] != "radio"): ?>
			<script type="text/javascript">
				$(document).ready(function(){
					<?php if ($field['Field']['field_type'] == "multi-dropdown"): ?>
						$("#<?php echo 'ArticleFieldData'.$field['Field']['id'].'Data'; ?>").rules("add", {
					<?php elseif ($field['Field']['field_type'] == "check"): ?>
						$("input[name='data[ArticleFieldData][<?= $field['Field']['id'] ?>][data][]']").rules("add", {
					<?php else: ?>
						$("#<?php echo 'ArticleValue'.$field['Field']['id'].'Data'; ?>").rules("add", {
					<?php endif ?>
						<?php $i = 0 ?>
						<?php foreach($rules as $row): $i++; ?>
							<?php if (!empty($row)): ?>
								<?php if ($i == $rules_count): ?>
									<?= str_replace(",","",$row) ?>
								<?php else: ?>
									<?= $row ?>
								<?php endif; ?>
								<?php
									if (strstr($row, "required: true")) { 
										$addClass = 1;
									}
								?>	
							<?php endif; ?>
						<?php endforeach; ?>
			    	});
			    	<?php if ($addClass == 1): ?>
			    		<?php if ($field['Field']['field_type'] == "multi-dropdown"): ?>
			    			$("#<?php echo 'ArticleFieldData'.$field['Field']['id'].'Data'; ?>").addClass('required');
			    		<?php elseif ($field['Field']['field_type'] == "check"): ?>
			    			$("input[name='data[ArticleFieldData][<?= $field['Field']['id'] ?>][data][]']").addClass('required');
			    		<?php else: ?>
			    			$("#<?php echo 'ArticleValue'.$field['Field']['id'].'Data'; ?>").addClass('required');
			    		<?php endif ?>
			    		<?php 
			    			if ($jqueryFunction == 0) {
			    				$jqueryFunction = 1;
			    			}
			    		?>
			    	<?php endif ?>
			   });
			</script>
		<?php else: ?>
			<?php
				if (is_numeric(
						array_search(
							"required: true,", 
							json_decode($field['Field']['rules']
							)
						)
					)) {
					$required = "required";
				}
			?>
		<?php endif ?>
	<?php endif; ?>
<?php endif; ?>

<?php if (end($fields) == $field && $jqueryFunction == 1): ?>
	<script type="text/javascript">
		$(document).ready(function(){   	
			changeRequiredFields();
		});
	</script>
<?php endif ?>

<?php
if ($field['Field']['field_type'] == "textarea"):
	if (empty($value)) {
		$value[0] = null;
	}
?>
	<?= $this->Form->input('ArticleValue.'.$field['Field']['id'].'.data', array('label' => $field['Field']['label'], 'rows' => 15, 'style' => 'width:500px', 'value' => $value[0])) ?>
<?php
elseif ($field['Field']['field_type'] == "text"):
?>
	<?= $this->Form->input('ArticleValue.'.$field['Field']['id'].'.data', array('label' => $field['Field']['label'], 'type' => 'text', 'value' => $value)) ?>
<?php
elseif ($field['Field']['field_type'] == "dropdown"):
	foreach (json_decode($field['Field']['field_options']) as $row) {
		$opt[$row] = $row;
	}
?>
	<?= $this->Form->input('ArticleValue.'.$field['Field']['id'].'.data', array('label' => $field['Field']['label'], 'type' => 'select', 'empty' => '- Choose -', 'options' => array($opt), 'value' => $value)) ?>
<?php
unset($opt);
elseif ($field['Field']['field_type'] == "radio"):
	foreach (json_decode($field['Field']['field_options']) as $row) {
		$opt[$row] = $row;
	}
?>
	<div class="input radio">
		<?= $this->Form->label('ArticleValue.'.$field['Field']['id'].'.data', ucfirst($field['Field']['title'])) ?>
		<?= $this->Form->radio('ArticleValue.'.$field['Field']['id'].'.data', $opt, array('legend' => false, 'hiddenField' => false, 'class' => $required, 'value' => $value[0])) ?>
	</div>
<?php
unset($opt);
elseif ($field['Field']['field_type'] == "multi-dropdown"):
	foreach (json_decode($field['Field']['field_options']) as $row) {
		$opt[$row] = $row;
	}
?>
	<?= $this->Form->input('ArticleFieldData.'.$field['Field']['id'].'.data', array('label' => $field['Field']['label'], 'multiple' => true, 'options' => $opt, 'value' => $value)) ?>

<?php
unset($opt);
elseif ($field['Field']['field_type'] == "check"):
	foreach (json_decode($field['Field']['field_options']) as $row) {
		$opt[$row] = $row;
	}
?>
	<div class="input checkbox <?= $required ?>">
		<?= $this->Form->input('ArticleFieldData.'.$field['Field']['id'].'.data', array('label' => $field['Field']['label'], 'multiple' => 'checkbox', 'options' => $opt, 'value' => $value)) ?>
	</div>
<?php
unset($opt);
elseif ($field['Field']['field_type'] == "file"):
?>
	<?= $this->Form->label(ucfirst($field['Field']['title'])) ?>
	<?= $this->Form->file('ArticleValue.'.$field['Field']['id'].'.data') ?>
	<?php if (!empty($value[0])): ?>
		<?= $this->Form->hidden('ArticleValue.'.$field['Field']['id'].'.filename', array(
			'value' => $value
		)) ?>
		<br />
		Current File: <?= $value[0] ?> 
		<?= $this->Form->input('ArticleValue.'.$field['Field']['id'].'.delete', array(
			'type' => 'checkbox',
			'label' => 'Unlink?'
		)) ?>
	<?php endif; ?>
<?php
elseif ($field['Field']['field_type'] == "img"):
?>
	<?= $this->Form->input('ArticleValue.'.$field['Field']['id'].'.data', array('label' => $field['Field']['label'], 'type' => 'text', 'value' => $value)) ?>
<?php
elseif ($field['Field']['field_type'] == "url"):
?>
	<?= $this->Form->input('ArticleValue.'.$field['Field']['id'].'.data', array('label' => $field['Field']['label'], 'type' => 'text', 'placeholder' => 'http://', 'value' => $value)) ?>
<?php
elseif ($field['Field']['field_type'] == "num"):
?>
	<?= $this->Form->input('ArticleValue.'.$field['Field']['id'].'.data', array('label' => $field['Field']['label'], 'type' => 'text', 'class' => 'input-mini', 'value' => $value)) ?>
<?php
elseif ($field['Field']['field_type'] == "email"):
?>
	<?= $this->Form->input('ArticleValue.'.$field['Field']['id'].'.data', array('label' => $field['Field']['label'], 'type' => 'text', 'value' => $value)) ?>
<?php
elseif ($field['Field']['field_type'] == "date"):
?>
	<?= $this->Form->input('ArticleValue.'.$field['Field']['id'].'.data', array('label' => $field['Field']['label'], 'type' => 'text', 'value' => $value)) ?>
<?php
endif;
	endforeach;
endif;
?>

<div id="text"></div>
<div class="field_options" style="margin-bottom: 9px">
		<?= $this->Form->input('field_options', array(
			'div' => false, 
			'style' => 'margin-bottom: 0',
			'type' => 'text',
			'label' => 'Tags'
		)) ?>
		<?= $this->Form->button('Add', array(
			'class' => 'btn btn-info', 
			'type' => 'button',
			'id' => 'add-data'
		)) ?>
</div>
<div id="field_data" style="width: 30%"></div>
<div id="field_existing_data" style="display: none">
	<?php
		$field_options = json_decode($this->request->data['Article']['tags']);
		if (count($field_options) == 0) {
			$field_style = "padding-top: 0px";
		} else {
			$field_style = "padding-top: 9px";
		}
	?>
	<?php if (count($field_options) > 0): ?>
		<?php foreach($field_options as $row): ?>
			<span><?= $row ?></span>
		<?php endforeach ?>
	<?php endif ?>
</div>

<div class="clear"></div>

<div class="input text publish_time" style="display: none;margin-top:9px">
	<?= $this->Form->input('publishing_date', array(
			'type' => 'text',
			'label' => 'Publish Time',
			'div' => false,
			'style' => 'width:70px',
			'value' => $time_part[0],
			'data-date-format' => 'yyyy-mm-dd'
	)) ?>

	<?= $this->Form->input('publishing_time', array(
			'type' => 'text',
			'label' => false,
			'div' => false,
			'style' => 'width:60px',
			'value' => $time_part[1]
	)) ?>

	<?= $this->Form->button('Submit', array(
		'type' => 'submit',
		'class' => 'btn'
	)) ?>
</div><br />

<?= $this->Form->input('id', array('type' => 'hidden')) ?>

<?= $this->Form->hidden('status', array('value' => $this->request->data['Article']['status'])) ?>

<?= $this->Form->button('Publish Now', array(
	'type' => 'submit',
	'class' => 'btn'
)) ?>
<?= $this->Form->button($draft.'Draft', array(
	'type' => 'submit',
	'style' => 'margin-left:5px;margin-right:5px',
	'class' => 'btn'
)) ?>
<?= $this->Form->button('Publish Later', array(
	'type' => 'button',
	'class' => 'btn'
)) ?>

<?= $this->Form->end(); ?>