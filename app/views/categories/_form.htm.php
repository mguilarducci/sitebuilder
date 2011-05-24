
<?php echo $this->form->create($action, array(
    'class' => 'form-edit',
    'object' => $category
)) ?>

<fieldset>
    <h2><?php echo __('categoria') ?></h2>
    <div class="field-group">
        <div class="form-grid-460 first">
            <?php echo $this->form->input('title', array(
                'label' => __('Nome da categoria'),
                'class' => 'ui-text large'
            )) ?>
        </div>

        <?php if($site->hasManyTypes()): ?>
            <div class="form-grid-460 first">
                <?php echo $this->form->input('type', array(
                    'label' => __('Tipo'),
                    'type' => 'select',
                    'class' => 'ui-select large',
                    'options' => Segments::listItemTypesFor($site->segment)
                )) ?>
            </div>
        <?php endif ?>

        <div class="form-grid-460 populate-fields">
            <label><?php echo __('Tipo de categoria'); ?></label>
            <?php echo $this->form->input("populate", array(
                "type" => "radio",
                "options" => array(
                    "auto" => "Automática",
                    "manual" => "Manual"
                )
            )); ?>
            <small><?php echo __('Categorias automáticas permitem a importação automática de conteúdo a partir de feeds RSS.'); ?></small>
            <small><?php echo __('Categorias manuais permitem a edição manual de todos os itens associados.'); ?></small>
        </div>

        <div class="form-grid-460 first">
            <?php echo $this->form->input('feed', array(
                'label' => __('URL do Feed'),
                'class' => 'ui-text large'
            )) ?>
        </div>

        <div class="form-grid-460 first">
            <?php echo $this->form->input('visibility', array(
                'type' => 'checkbox',
                'label' => __('Visibilidade')
            )) ?>
            <label for="FormVisibility" class="checkbox"><?php echo __('Esta categoria está visível e disponível para os usuários do site mobile') ?></label>
        </div>

        <?php echo $this->form->input('parent_id', array(
            'type' => 'hidden',
            'value' => $parent->id
        )) ?>
    </div>
</fieldset>

<fieldset class="actions">
    <?php echo $this->form->submit(__('Salvar'), array(
        'class' => 'ui-button red larger'
    )) ?>
    <?php if($category->id > 0) {
    echo $this->html->link(
        $this->html->image('shared/categories/delete.gif') . __('Apagar categoria'),
        '/categories/delete/' . $category->id,
        array( 'class' => 'ui-button delete' )
    ); } ?>
</fieldset>

<?php echo $this->form->close() ?>
