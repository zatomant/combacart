function isObject(obj){
	return obj !== null && typeof obj === 'object';
}

function ToastHide(){
	jQuery('#dlgSpinner').modal('hide');
}

function showDlg(dlg,txt, keyboard){
	jQuery('#'+dlg+' span:eq(0)').html(txt);
	let myModal = new bootstrap.Modal(jQuery("#"+dlg), {
		keyboard: keyboard
	});
	myModal.show();
}

function showInfo(txt) {
    let timeout = 3000;
	if (isObject(txt)){
        txt = txt.txt;
        if (txt.timeout) txt.timeout;
    }
	showDlg('dlgInfo',txt,true);

    setTimeout(function() {
        jQuery('#dlgInfo').modal('hide');
    }, timeout);
}

function showSpinner(obj) {
    const strTxt = isObject(obj) ? obj.txt : obj;

    $('#dlgSpinner span:eq(0)').text(strTxt);
    const myModalEl = document.querySelector('#dlgSpinner');
    const myModal = bootstrap.Modal.getOrCreateInstance(myModalEl);
    myModal.show();
    return myModalEl;
}

function showError(txt) {
	if (isObject(txt)) txt = txt.txt;
	showDlg('dlgError',txt,true);
}

function showErrorModal(txt) {
	if (isObject(txt)) txt = txt.txt;
	showDlg('dlgErrorModal',txt,false);
}

function showErrorCritical(txt) {
	if (isObject(txt)) txt = txt.txt;
	showDlg('dlgErrorModal',txt,false);
}

function showConfirm(obj) {
	bootbox.confirm({
		message: obj.txt,
		className: 'modal-sm text-center bg-body-tertiary bg-opacity-75',
		centerVertical: true,
		animate: true,
		closeButton: false,
		swapButtonOrder: true,
		buttons: {
			confirm: {
				label: 'ОК',
				className: 'btn-success'
			},
			cancel: {
				label: 'Cancel',
				className: 'btn-outline-danger'
			}
		},
		callback: obj.callback
	});
}
