<div class="slide-header">
	<div class="grid-4 first">&nbsp;</div>
	<div class="grid-8">
		<h1><?php echo $this->pageTitle = s('Edit Skin') ?></h1>
	</div>
	<div class="clear"></div>
</div>

<?php echo $this->form->create('/skins/edit/' . $skin->id(), array(
	'id' => 'form-custom-theme',
	'class' => 'form-edit default-form',
	'method' => 'file'
)) ?>

	<?php $custom = false; ?>
	<?php echo $this->element('skins/form', compact('theme', 'skin', 'site', 'custom')) ?>

	<fieldset class="actions">
		<?php echo $this->form->submit(s('Save and Continue'), array(
			'class' => 'ui-button red larger save-continue',
			'name' => 'continue',
			'value' => 1
		)) ?>

		<?php echo $this->form->submit(s('Save'), array(
			'class' => 'ui-button red larger save',
			'name' => 'continue',
			'value' => 0
		)) ?>
	</fieldset>

<?php echo $this->form->close() ?>
