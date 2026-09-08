ve.ui.MWFormulaDialog = function VeUiMWFormulaDialog(config) {
	ve.ui.MWFormulaDialog.super.call(this, config);
};

OO.inheritClass(ve.ui.MWFormulaDialog, ve.ui.MWExtensionPreviewDialog);

ve.ui.MWFormulaDialog.static.size = 'large';
ve.ui.MWFormulaDialog.static.dir = 'ltr';

ve.ui.MWFormulaDialog.prototype.initialize = function () {
	ve.ui.MWFormulaDialog.super.prototype.initialize.call(this);

	this.previewElement = new ve.ui.MWPreviewElement(null, { useView: true });
	this.previewElement.$element.addClass('ve-ui-smjFormulaDialog-preview');

	const originalUpdatePreview = this.previewElement.updatePreview.bind(this.previewElement);
	this.previewElement.updatePreview = () => {
		this.previewElement.$element.empty();
		originalUpdatePreview();
	};

	this.displaySelect = new OO.ui.ButtonSelectWidget({
		items: [
			new OO.ui.ButtonOptionWidget({
				data: 'default',
				label: ve.msg('simplemathjax-visualeditor-mwformuladialog-display-default')
			}),
			new OO.ui.ButtonOptionWidget({
				data: 'inline',
				label: ve.msg('simplemathjax-visualeditor-mwformuladialog-display-inline')
			}),
			new OO.ui.ButtonOptionWidget({
				data: 'block',
				label: ve.msg('simplemathjax-visualeditor-mwformuladialog-display-block')
			})
		]
	});

	const inputField = new OO.ui.FieldLayout(this.input, {
		align: 'top',
		label: ve.msg('simplemathjax-visualeditor-mwformuladialog-formula')
	});
	const displayField = new OO.ui.FieldLayout(this.displaySelect, {
		align: 'top',
		label: ve.msg('simplemathjax-visualeditor-mwformuladialog-display')
	});

	this.$body
		.addClass('ve-ui-smjFormulaDialog-content')
		.append(
			this.previewElement.$element,
			inputField.$element,
			displayField.$element
		);
};

ve.ui.MWFormulaDialog.prototype.getSetupProcess = function (data) {
	return ve.ui.MWFormulaDialog.super.prototype.getSetupProcess.call(this, data)
		.next(() => {
			const attributes = this.selectedNode && this.selectedNode.getAttribute('mw').attrs,
				display = attributes && attributes.display || 'default',
				isReadOnly = this.isReadOnly();

			this.displaySelect.selectItemByData(display).setDisabled(isReadOnly);

			this.input.on('change', this.onChangeHandler);
			this.displaySelect.on('choose', this.onChangeHandler);
		});
};

ve.ui.MWFormulaDialog.prototype.getReadyProcess = function (data) {
	return ve.ui.MWFormulaDialog.super.prototype.getReadyProcess.call(this, data)
		.next(() => {
			this.input.focus().moveCursorToEnd();
		});
};

ve.ui.MWFormulaDialog.prototype.getTeardownProcess = function (data) {
	return ve.ui.MWFormulaDialog.super.prototype.getTeardownProcess.call(this, data)
		.first(() => {
			this.input.off('change', this.onChangeHandler);
			this.displaySelect.off('choose', this.onChangeHandler);
		});
};

ve.ui.MWFormulaDialog.prototype.updateMwData = function (mwData) {
	ve.ui.MWFormulaDialog.super.prototype.updateMwData.call(this, mwData);

	const display = this.displaySelect.findSelectedItem() &&
		this.displaySelect.findSelectedItem().getData();
	mwData.attrs.display = display && display !== 'default' ? display : undefined;
};

ve.ui.MWFormulaDialog.prototype.getBodyHeight = function () {
	return 350;
};
